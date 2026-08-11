#!/usr/bin/env bun

import { loginCommand } from "./commands/login";
import { projectsCommand, projectCreateCommand, useProjectCommand } from "./commands/projects";
import { listCommand, inspectCommand, setCommand, getCommand, copyCommand, templateCommand, diffCommand, pullCommand } from "./commands/variables";
import { interactiveCommand } from "./commands/interactive";
import { clearAuth, loadConfig, saveConfig } from "./config";
import { apiRequest } from "./api";

const args = process.argv.slice(2);
const command = args[0];
const subArg1 = args[1];

// Parse simple flags like --json, --project=slug, --env=slug, -h, --help
const flags: Record<string, any> = {};
for (const arg of args) {
  if (arg === "--json") flags.json = true;
  else if (arg === "--help" || arg === "-h") flags.help = true;
  else if (arg.startsWith("--project=")) flags.project = arg.split("=")[1];
  else if (arg.startsWith("--env=")) flags.env = arg.split("=")[1];
}

const commandHelp: Record<string, { description: string; usage: string; flags?: string[]; examples?: string[] }> = {
  login: {
    description: "Authorize this CLI device with your Keysha account via browser approval.",
    usage: "keysha login",
    examples: ["keysha login"],
  },
  logout: {
    description: "Log out and clear local authentication credentials.",
    usage: "keysha logout",
    examples: ["keysha logout"],
  },
  whoami: {
    description: "Display currently authenticated user and active workspace details.",
    usage: "keysha whoami",
    examples: ["keysha whoami"],
  },
  projects: {
    description: "List all projects and environments in the active workspace.",
    usage: "keysha projects [--json]",
    flags: ["--json            Output response as formatted JSON"],
    examples: ["keysha projects", "keysha projects --json"],
  },
  project: {
    description: "Manage projects in your workspace.",
    usage: "keysha project create <name>",
    examples: ["keysha project create mingle"],
  },
  use: {
    description: "Set default active project for CLI commands.",
    usage: "keysha use <project-slug>",
    examples: ["keysha use mingle"],
  },
  list: {
    description: "List variables and status for a project and environment (supports dev, prev, prod).",
    usage: "keysha list [dev|prev|prod] [--project=<slug>] [--dev|--prod|--prev]",
    flags: [
      "--project=<slug>  Specify target project slug",
      "--dev/--prod      Target development or production environment",
      "--json            Output response as formatted JSON",
    ],
    examples: ["keysha list", "keysha list dev", "keysha list prod --project=mingle"],
  },
  inspect: {
    description: "Inspect variable metadata, classification, and provider details.",
    usage: "keysha inspect <KEY> [--project=<slug>] [--json]",
    flags: ["--project=<slug>  Specify target project slug", "--json            Output response as formatted JSON"],
    examples: ["keysha inspect STRIPE_SECRET_KEY"],
  },
  set: {
    description: "Set or update a credential variable value for a project environment.",
    usage: "keysha set <KEY> [dev|prev|prod] [--project=<slug>]",
    flags: [
      "--project=<slug>  Specify target project slug",
      "--dev/--prod      Target development or production environment",
    ],
    examples: ["keysha set STRIPE_SECRET_KEY", "keysha set RESEND_API_KEY dev"],
  },
  get: {
    description: "Output raw plaintext variable value for shell scripts or pipelines.",
    usage: "keysha get <KEY> [dev|prev|prod] [--project=<slug>]",
    flags: [
      "--project=<slug>  Specify target project slug",
      "--dev/--prod      Target development or production environment",
    ],
    examples: ["keysha get STRIPE_SECRET_KEY prod", "keysha get DB_URL dev"],
  },
  copy: {
    description: "Retrieve plaintext variable value and copy directly to OS clipboard.",
    usage: "keysha copy <KEY> [dev|prev|prod] [--project=<slug>]",
    flags: [
      "--project=<slug>  Specify target project slug",
      "--dev/--prod      Target development or production environment",
    ],
    examples: ["keysha copy STRIPE_SECRET_KEY prod", "keysha copy AWS_SECRET_KEY dev"],
  },
  template: {
    description: "Output .env.example template directly to stdout or to a target file.",
    usage: "keysha template [filepath] [--project=<slug>]",
    flags: ["--project=<slug>  Specify target project slug"],
    examples: ["keysha template", "keysha template .env.example", "keysha template apps/web/.env.example"],
  },
  diff: {
    description: "Compare environment completeness across project environments.",
    usage: "keysha diff [dev|prev|prod] [--project=<slug>]",
    flags: ["--project=<slug>  Specify target project slug"],
    examples: ["keysha diff", "keysha diff dev prod", "keysha diff --dev --prod"],
  },
  pull: {
    description: "Export project environment variables directly to a local .env file without overwriting unmanaged content.",
    usage: "keysha pull [dev|prev|prod] [filepath] [--project=<slug>]",
    flags: [
      "--project=<slug>  Specify target project slug",
      "--dev/--prod      Target development or production environment",
    ],
    examples: ["keysha pull dev", "keysha pull dev .env.local", "keysha pull prod apps/web/src/.env"],
  },
};

function printCommandHelp(cmd: string) {
  const info = commandHelp[cmd];
  if (!info) {
    console.log(`No specific help entry for '${cmd}'. Run 'keysha --help' for all commands.`);
    return;
  }

  console.log(`\n🔑 KEYSHA CLI — ${cmd.toUpperCase()}`);
  console.log(`\n${info.description}`);
  console.log(`\nUSAGE\n  ${info.usage}\n`);

  if (info.flags && info.flags.length > 0) {
    console.log("FLAGS");
    for (const flag of info.flags) {
      console.log(`  ${flag}`);
    }
    console.log();
  }

  if (info.examples && info.examples.length > 0) {
    console.log("EXAMPLES");
    for (const ex of info.examples) {
      console.log(`  $ ${ex}`);
    }
    console.log();
  }
}

function printHelp() {
  console.log(`
🔑 KEYSHA CLI v1.0.0
Envelope encryption & configuration vault for developers

USAGE
  keysha <command> [arguments] [flags]

COMMANDS
  login                    Authorize this CLI device with your Keysha account
  logout                   Log out and remove local credentials
  whoami                   Show current authenticated user and active workspace
  projects                 List all projects and environments
  project create <name>    Create a new project with default environments
  use <project>            Set active project for CLI commands
  list [env]               List variables in project / environment
  inspect <key>            Inspect metadata and provider for a variable
  set <key> [value]        Set or update a variable credential value
  get <key> [env]          Retrieve plaintext variable value
  copy <key> [env]         Copy plaintext variable value directly to clipboard
  template [env]           Output .env.example template for project
  diff [env1] [env2]       Compare environment variable completeness
  pull [env] [file]        Pull decrypted .env file directly to local disk
  help, --help, -h         Show this help information

FLAGS
  --json                   Output responses as raw JSON
  --project=<slug>         Override target project
  --env=<slug>             Override target environment

EXAMPLES
  keysha project create mingle
  keysha set STRIPE_SECRET_KEY
  keysha copy STRIPE_SECRET_KEY
  keysha diff development production

Tip: Run 'keysha <command> --help' or 'keysha help <command>' for specific command usage.
`);
}

async function main() {
  if (!command) {
    await interactiveCommand();
    return;
  }

  // Handle keysha help <command>
  if (command === "help" && subArg1 && commandHelp[subArg1]) {
    printCommandHelp(subArg1);
    return;
  }

  // Handle keysha <command> --help or keysha <command> -h
  if (flags.help && commandHelp[command]) {
    printCommandHelp(command);
    return;
  }

  switch (command) {
    case "help":
    case "--help":
    case "-h":
      printHelp();
      break;

    case "--version":
    case "-v":
      console.log("Keysha CLI v1.0.0 — Built with Bun & TypeScript");
      break;

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
      } else if (flags.help || !subArg1) {
        printCommandHelp("project");
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
      await setCommand(args, flags);
      break;

    case "get":
      await getCommand(args, flags);
      break;

    case "copy":
      await copyCommand(args, flags);
      break;

    case "template":
      await templateCommand(args, flags);
      break;

    case "diff":
      await diffCommand(args, flags);
      break;

    case "pull":
      await pullCommand(args, flags);
      break;

    default:
      console.log(`Unknown command: ${command}\n`);
      printHelp();
      break;
  }
}

main().catch((err) => {
  console.error(`Fatal: ${err.message}`);
  process.exit(1);
});
