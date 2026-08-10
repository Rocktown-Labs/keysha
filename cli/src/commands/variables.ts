import { apiRequest } from "../api";
import { loadConfig, saveConfig } from "../config";
import { readFileSync, existsSync } from "fs";

export async function listCommand(args: string[], flags: Record<string, any>) {
  const config = loadConfig();
  const project = flags.project || config.activeProject;
  const env = flags.env || config.activeEnvironment || "production";

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

export async function inspectCommand(key: string, flags: Record<string, any>) {
  const config = loadConfig();
  const project = flags.project || config.activeProject;

  if (!project) {
    console.error("❌ No active project specified.");
    return;
  }

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

export async function setCommand(key: string, flags: Record<string, any>) {
  const config = loadConfig();
  const project = flags.project || config.activeProject;
  const env = flags.env || config.activeEnvironment || "production";

  if (!project) {
    console.error("❌ No active project specified.");
    return;
  }

  if (!key) {
    console.error("Usage: keysha set <KEY>");
    return;
  }

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

export async function getCommand(key: string, flags: Record<string, any>) {
  const config = loadConfig();
  const project = flags.project || config.activeProject;
  const env = flags.env || config.activeEnvironment || "production";

  if (!project || !key) {
    console.error("Usage: keysha get <KEY>");
    return;
  }

  const res = await apiRequest(`/projects/${project}/variables/${key}/value?env=${env}`);

  // Pure stdout output contract per Section 94
  process.stdout.write(res.value + "\n");
}

export async function copyCommand(key: string, flags: Record<string, any>) {
  const config = loadConfig();
  const project = flags.project || config.activeProject;
  const env = flags.env || config.activeEnvironment || "production";

  if (!project || !key) {
    console.error("Usage: keysha copy <KEY>");
    return;
  }

  const res = await apiRequest(`/projects/${project}/variables/${key}/value?env=${env}`);

  try {
    const proc = Bun.spawn(["pbcopy"], { stdin: "pipe" });
    proc.stdin.write(res.value);
    proc.stdin.end();
  } catch {}

  console.log(`✓ Copied ${key} to clipboard`);
}

export async function templateCommand(flags: Record<string, any>) {
  const config = loadConfig();
  const project = flags.project || config.activeProject;

  if (!project) {
    console.error("❌ No active project specified.");
    return;
  }

  const text = await apiRequest(`/projects/${project}/template`);
  process.stdout.write(text + "\n");
}

export async function diffCommand(flags: Record<string, any>) {
  const config = loadConfig();
  const project = flags.project || config.activeProject;

  if (!project) {
    console.error("❌ No active project specified.");
    return;
  }

  const res = await apiRequest(`/projects/${project}/diff`);

  console.log(`\nEnvironment Comparison for ${res.project}\n`);
  const envs = res.environments as string[];
  const header = "VARIABLE".padEnd(32) + envs.map((e) => e.toUpperCase().padEnd(12)).join("");
  console.log(header);
  console.log("-".repeat(header.length));

  for (const row of res.diff) {
    let line = row.key.padEnd(32);
    for (const env of envs) {
      line += (row[env] ? "✓" : "✗").padEnd(12);
    }
    console.log(line);
  }
}
