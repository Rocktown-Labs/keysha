import { loadConfig } from "../config";
import { apiRequest } from "../api";
import { listCommand, diffCommand, templateCommand } from "./variables";
import { projectsCommand } from "./projects";

export async function interactiveCommand() {
  const config = loadConfig();

  console.log("\n╭─ Keysha Vault CLI ──────────────────────────────────────╮");
  console.log(`│ Active Host:        ${config.activeHost}`);
  console.log(`│ Active Project:     ${config.activeProject || "None"}`);
  console.log(`│ Active Environment: ${config.activeEnvironment || "production"}`);
  console.log("╰─────────────────────────────────────────────────────────╯\n");

  console.log("Available Actions:");
  console.log("  1) Browse variables (keysha list)");
  console.log("  2) Compare environments (keysha diff)");
  console.log("  3) View .env.example (keysha template)");
  console.log("  4) List projects (keysha projects)");
  console.log("  5) Login (keysha login)");
  console.log("  6) Exit\n");

  process.stdout.write("Select option (1-6): ");
  const option = await new Promise<string>((resolve) => {
    process.stdin.setRawMode?.(false);
    process.stdin.once("data", (d) => resolve(d.toString().trim()));
  });

  switch (option) {
    case "1":
      await listCommand([], {});
      break;
    case "2":
      await diffCommand({});
      break;
    case "3":
      await templateCommand({});
      break;
    case "4":
      await projectsCommand({});
      break;
    case "5":
      const { loginCommand } = await import("./login");
      await loginCommand();
      break;
    default:
      console.log("Exiting Keysha CLI.");
      break;
  }
}
