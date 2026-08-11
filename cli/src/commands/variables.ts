import { apiRequest } from "../api";
import { loadConfig, saveConfig } from "../config";

export function normalizeEnv(rawEnv: string | undefined): string {
  if (!rawEnv) return "production";
  const clean = rawEnv.trim().toLowerCase();
  if (clean === "dev" || clean === "development" || clean === "local") return "development";
  if (clean === "prod" || clean === "production") return "production";
  if (clean === "prev" || clean === "preview" || clean === "stage" || clean === "staging") return "preview";
  return clean;
}

async function resolveTarget(rawKey: string | undefined, flags: Record<string, any>) {
  const config = loadConfig();
  let project = flags.project || config.activeProject;
  let key = rawKey ? rawKey.trim() : undefined;

  // Parse "project/KEY" syntax (e.g., keysha/KEYSHA_RECOVERY_KEY)
  if (key && key.includes("/")) {
    const parts = key.split("/");
    project = parts[0];
    key = parts.slice(1).join("/");
  }

  const rawEnv = flags.env || config.activeEnvironment || "production";
  const env = normalizeEnv(rawEnv);

  // Auto-resolve project by searching workspace projects if key exists in another project
  if (!flags.project && key) {
    try {
      const res = await apiRequest("/projects");
      if (res.projects && Array.isArray(res.projects)) {
        if (res.projects.length === 1 && !project) {
          project = res.projects[0].slug;
          saveConfig({ ...config, activeProject: project });
          console.log(`ℹ Auto-selected project: ${res.projects[0].name} (${project})`);
        } else {
          for (const p of res.projects) {
            try {
              const vRes = await apiRequest(`/projects/${p.slug}/variables?env=${env}`);
              if (vRes.variables && vRes.variables.some((v: any) => v.key.toUpperCase().includes(key!.toUpperCase()) || key!.toUpperCase().includes(v.key.toUpperCase()))) {
                project = p.slug;
                break;
              }
            } catch {}
          }
        }
      }
    } catch {}
  }

  // Fallback to active project or first project
  if (!project) {
    try {
      const res = await apiRequest("/projects");
      if (res.projects && res.projects.length > 0) {
        project = res.projects[0].slug;
        saveConfig({ ...config, activeProject: project });
      }
    } catch {}
  }

  return {
    project,
    key,
    env,
  };
}

async function findMatchingKey(project: string, env: string, targetKey: string): Promise<string> {
  const cleanTarget = targetKey.toUpperCase().replace(/[^A-Z0-9]/g, "");

  try {
    const res = await apiRequest(`/projects/${project}/variables?env=${env}`);
    if (res.variables && Array.isArray(res.variables)) {
      // 1. Exact case-insensitive match
      const exactMatch = res.variables.find((v: any) => v.key.toUpperCase() === targetKey.toUpperCase());
      if (exactMatch) return exactMatch.key;

      // 2. Normalized match (ignoring underscores/hyphens/case)
      const normMatch = res.variables.find((v: any) => v.key.toUpperCase().replace(/[^A-Z0-9]/g, "") === cleanTarget);
      if (normMatch) return normMatch.key;

      // 3. Substring match (e.g. recovery_key matches KEYSHA_RECOVERY_KEY)
      const subMatch = res.variables.find((v: any) => v.key.toUpperCase().includes(targetKey.toUpperCase()) || targetKey.toUpperCase().includes(v.key.toUpperCase()));
      if (subMatch) return subMatch.key;
    }
  } catch {}

  return targetKey;
}

export async function listCommand(args: string[], flags: Record<string, any>) {
  const posEnv = args[1] && ["dev", "prod", "prev", "development", "production", "preview", "local", "stage"].includes(args[1].toLowerCase()) ? normalizeEnv(args[1]) : flags.env;
  const { project, env } = await resolveTarget(undefined, { ...flags, env: posEnv });

  if (!project) {
    console.error("❌ No active project specified. Use: keysha use <project-slug> or --project=<slug>");
    return;
  }

  const res = await apiRequest(`/projects/${project}/variables?env=${env}`);

  if (flags.json) {
    console.log(JSON.stringify(res, null, 2));
    return;
  }

  console.log(`\nProject:     ${res.project}`);
  console.log(`Environment: ${res.environment}\n`);

  console.log("KEY                              PROVIDER     TYPE       STATUS");
  console.log("------------------------------------------------------------------");
  for (const v of res.variables) {
    const key = v.key.padEnd(32);
    const provider = (v.provider || "custom").padEnd(12);
    const type = (v.classification || "secret").padEnd(10);
    const status = v.configured ? "configured" : "missing";
    console.log(`${key} ${provider} ${type} ${status}`);
  }
}

export async function inspectCommand(rawKey: string, flags: Record<string, any>) {
  const { project, key: initialKey } = await resolveTarget(rawKey, flags);

  if (!project) {
    console.error("❌ No active project specified. Use: keysha use <project-slug> or --project=<slug>");
    return;
  }

  if (!initialKey) {
    console.error("Usage: keysha inspect <KEY> [--project=<slug>]");
    return;
  }

  const key = await findMatchingKey(project, flags.env || "production", initialKey);
  const res = await apiRequest(`/projects/${project}/variables/${key}`);

  if (flags.json) {
    console.log(JSON.stringify(res, null, 2));
    return;
  }

  console.log(`\nName:           ${res.key}`);
  console.log(`Provider:       ${res.provider}`);
  console.log(`Classification: ${res.classification}`);
  console.log(`Required:       ${res.required ? "yes" : "no"}`);
  console.log(`Description:    ${res.description || "N/A"}`);
  console.log(`Last Updated:   ${res.updated_at}`);
}

export async function setCommand(args: string[], flags: Record<string, any>) {
  const rawKey = args[1];
  const posEnv = args[2] && ["dev", "prod", "prev", "development", "production", "preview"].includes(args[2].toLowerCase()) ? normalizeEnv(args[2]) : flags.env;
  const { project, key: initialKey, env } = await resolveTarget(rawKey, { ...flags, env: posEnv });

  if (!project) {
    console.error("❌ No active project specified. Use: keysha use <project-slug> or --project=<slug>");
    return;
  }

  if (!initialKey) {
    console.error("Usage: keysha set <KEY> [dev|prev|prod] [--project=<slug>]");
    return;
  }

  const key = await findMatchingKey(project, env, initialKey);

  process.stdout.write(`Enter value for ${key} [${project}/${env}]: `);
  const value = await new Promise<string>((resolve) => {
    let input = "";
    process.stdin.setRawMode?.(true);
    process.stdin.resume();
    process.stdin.on("data", function handler(chunk) {
      const str = chunk.toString();
      if (str === "\r" || str === "\n") {
        process.stdin.setRawMode?.(false);
        process.stdin.pause();
        process.stdin.removeListener("data", handler);
        console.log();
        resolve(input);
      } else if (str === "\u0003") {
        process.exit();
      } else if (str === "\u007f") {
        if (input.length > 0) input = input.slice(0, -1);
      } else {
        input += str;
      }
    });
  });

  const res = await apiRequest(`/variables/set`, {
    method: "POST",
    body: JSON.stringify({
      project_slug: project,
      environment_slug: env,
      key,
      value: value.trim(),
    }),
  });

  console.log(`✓ Saved variable ${res.key} for ${res.environment}`);
}

export async function getCommand(args: string[], flags: Record<string, any>) {
  const rawKey = args[1];
  const posEnv = args[2] && ["dev", "prod", "prev", "development", "production", "preview"].includes(args[2].toLowerCase()) ? normalizeEnv(args[2]) : flags.env;
  const { project, key: initialKey, env } = await resolveTarget(rawKey, { ...flags, env: posEnv });

  if (!project || !initialKey) {
    console.error("Usage: keysha get <KEY> [dev|prev|prod] [--project=<slug>]");
    return;
  }

  try {
    const key = await findMatchingKey(project, env, initialKey);
    const res = await apiRequest(`/projects/${project}/variables/${key}/value?env=${env}`);
    process.stdout.write(res.value + "\n");
  } catch (err: any) {
    console.error(`❌ ${err.message || "Could not retrieve variable value."}`);
  }
}

export async function copyCommand(args: string[], flags: Record<string, any>) {
  const rawKey = args[1];
  const posEnv = args[2] && ["dev", "prod", "prev", "development", "production", "preview"].includes(args[2].toLowerCase()) ? normalizeEnv(args[2]) : flags.env;
  const { project, key: initialKey, env } = await resolveTarget(rawKey, { ...flags, env: posEnv });

  if (!project || !initialKey) {
    console.error("Usage: keysha copy <KEY> [dev|prev|prod] [--project=<slug>]");
    return;
  }

  try {
    const key = await findMatchingKey(project, env, initialKey);
    const res = await apiRequest(`/projects/${project}/variables/${key}/value?env=${env}`);

    try {
      const proc = Bun.spawn(["pbcopy"], { stdin: "pipe" });
      proc.stdin.write(res.value);
      proc.stdin.end();
    } catch {}

    console.log(`✓ Copied ${key} [${env}] to clipboard!`);
  } catch (err: any) {
    console.error(`❌ ${err.message || "Could not retrieve variable value."}`);
  }
}

export async function templateCommand(args: string[], flags: Record<string, any>) {
  const { project } = await resolveTarget(undefined, flags);

  if (!project) {
    console.error("❌ No active project specified.");
    return;
  }

  const targetFile = args[1] && (args[1].includes(".") || args[1].includes("/")) ? args[1] : (flags.file || undefined);
  const text = await apiRequest(`/projects/${project}/template`);

  if (targetFile) {
    const fs = require("fs");
    const path = require("path");
    const fullPath = path.resolve(process.cwd(), targetFile);
    const dir = path.dirname(fullPath);
    if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
    fs.writeFileSync(fullPath, text + "\n", "utf-8");
    console.log(`✓ Created template file ${targetFile} for ${project}`);
  } else {
    process.stdout.write(text + "\n");
  }
}

export async function diffCommand(args: string[], flags: Record<string, any>) {
  const { project } = await resolveTarget(undefined, flags);

  if (!project) {
    console.error("❌ No active project specified.");
    return;
  }

  const res = await apiRequest(`/projects/${project}/diff`);

  let targetEnvs = res.environments as string[];
  const rawPosEnvs = args.slice(1).filter((a) => !a.startsWith("-"));
  if (rawPosEnvs.length > 0) {
    targetEnvs = rawPosEnvs.map((e) => normalizeEnv(e));
  } else if (flags.targetEnvs && Array.isArray(flags.targetEnvs) && flags.targetEnvs.length > 0) {
    targetEnvs = flags.targetEnvs.map((e: string) => normalizeEnv(e));
  }

  const getEnvLabel = (e: string) => {
    if (e === "development") return "DEV";
    if (e === "production") return "PROD";
    if (e === "preview") return "PREV";
    return e.toUpperCase();
  };

  console.log(`\nEnvironment Comparison for ${res.project}\n`);
  const header = "VARIABLE".padEnd(32) + targetEnvs.map((e) => getEnvLabel(e).padEnd(12)).join("");
  console.log(header);
  console.log("-".repeat(header.length));

  for (const row of res.diff) {
    let line = row.key.padEnd(32);
    for (const env of targetEnvs) {
      line += (row[env] ? "✓" : "✗").padEnd(12);
    }
    console.log(line);
  }
}

export async function pullCommand(args: string[], flags: Record<string, any>) {
  const envArg = args[1] && !args[1].includes("/") && !args[1].includes(".") ? args[1] : undefined;
  const targetFile = args[2] || (args[1] && (args[1].includes("/") || args[1].includes(".")) ? args[1] : ".env");
  const { project, env } = await resolveTarget(undefined, { ...flags, env: envArg || flags.env || "development" });

  if (!project) {
    console.error("❌ No active project specified. Use: keysha use <project-slug> or --project=<slug>");
    return;
  }

  try {
    const text = await apiRequest(`/projects/${project}/export?env=${env}`);
    const fs = require("fs");
    const path = require("path");
    const fullPath = path.resolve(process.cwd(), targetFile);

    if (fs.existsSync(fullPath)) {
      let existingContent = fs.readFileSync(fullPath, "utf-8");
      const newLines = text.trim().split("\n");
      let updatedCount = 0;

      for (const line of newLines) {
        if (!line.includes("=")) continue;
        const [k] = line.split("=");
        const regex = new RegExp(`^${k}=.*$`, "m");
        if (regex.test(existingContent)) {
          existingContent = existingContent.replace(regex, line);
        } else {
          existingContent += (existingContent.endsWith("\n") ? "" : "\n") + line + "\n";
        }
        updatedCount++;
      }

      fs.writeFileSync(fullPath, existingContent, "utf-8");
      console.log(`✓ Updated ${updatedCount} variables in ${targetFile} [${project}/${env}]`);
    } else {
      const dir = path.dirname(fullPath);
      if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
      fs.writeFileSync(fullPath, text, "utf-8");
      const count = text.trim().split("\n").filter((l: string) => l.includes("=")).length;
      console.log(`✓ Exported ${count} variables to ${targetFile} [${project}/${env}]`);
    }
  } catch (err: any) {
    console.error(`❌ ${err.message || "Failed to pull environment variables."}`);
  }
}

