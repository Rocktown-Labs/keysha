import { apiRequest } from "../api";
import { loadConfig, saveConfig } from "../config";

export async function projectsCommand(flags: Record<string, any>) {
  const res = await apiRequest("/projects");

  if (flags.json) {
    console.log(JSON.stringify(res, null, 2));
    return;
  }

  console.log("\nKEYSHA PROJECTS\n");
  for (const p of res.projects) {
    const envs = p.environments.join(", ");
    console.log(`• ${p.name} (${p.slug})`);
    console.log(`  Environments: ${envs}`);
    console.log(`  Variables:    ${p.variables_count} expected\n`);
  }
}

export async function projectCreateCommand(name: string) {
  if (!name) {
    console.error("Usage: keysha project create <name>");
    return;
  }

  const res = await apiRequest("/projects", {
    method: "POST",
    body: JSON.stringify({ name }),
  });

  saveConfig({ activeProject: res.project.slug });

  console.log(`✓ Project '${res.project.name}' created and set as active.`);
}

export async function useProjectCommand(slug: string) {
  if (!slug) {
    console.error("Usage: keysha use <project-slug>");
    return;
  }

  saveConfig({ activeProject: slug });
  console.log(`✓ Active project set to '${slug}'.`);
}
