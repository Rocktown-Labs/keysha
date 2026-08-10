#!/usr/bin/env bun

import { loginCommand } from "./commands/login";
import { projectsCommand, projectCreateCommand, useProjectCommand } from "./commands/projects";
import { listCommand, inspectCommand, setCommand, getCommand, copyCommand, templateCommand, diffCommand } from "./commands/variables";
import { interactiveCommand } from "./commands/interactive";
import { clearAuth, loadConfig, saveConfig } from "./config";
import { apiRequest } from "./api";

const args = process.argv.slice(2);
const command = args[0];
const subArg1 = args[1];

// Parse simple flags like --json, --project=slug, --env=slug
const flags: Record<string, any> = {};
for (const arg of args) {
  if (arg === "--json") flags.json = true;
  else if (arg.startsWith("--project=")) flags.project = arg.split("=")[1];
  else if (arg.startsWith("--env=")) flags.env = arg.split("=")[1];
}

async function main() {
  if (!command) {
    await interactiveCommand();
    return;
  }

  switch (command) {
    case "login":
      await loginCommand();
      break;

    case "logout":
      clearAuth();
      console.log("✓ Logged out successfully.");
      break;

    case "whoami":
      try {
        const res = await apiRequest("/whoami");
        console.log(`User:      ${res.user.name} (${res.user.email})`);
        console.log(`Workspace: ${res.workspace.name} (${res.workspace.slug})`);
      } catch (err: any) {
        console.error(`❌ ${err.message}`);
      }
      break;

    case "projects":
      await projectsCommand(flags);
      break;

    case "project":
      if (subArg1 === "create") {
        await projectCreateCommand(args[2]);
      }
      break;

    case "use":
      await useProjectCommand(subArg1);
      break;

    case "list":
      await listCommand(args, flags);
      break;

    case "inspect":
      await inspectCommand(subArg1, flags);
      break;

    case "set":
      await setCommand(subArg1, flags);
      break;

    case "get":
      await getCommand(subArg1, flags);
      break;

    case "copy":
      await copyCommand(subArg1, flags);
      break;

    case "template":
      await templateCommand(flags);
      break;

    case "diff":
      await diffCommand(flags);
      break;

    default:
      console.log(`Unknown command: ${command}`);
      console.log("Available commands: login, logout, whoami, projects, use, list, inspect, set, get, copy, template, diff");
      break;
  }
}

main().catch((err) => {
  console.error(`Fatal: ${err.message}`);
  process.exit(1);
});
