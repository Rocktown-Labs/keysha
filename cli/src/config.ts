import { existsSync, readFileSync, writeFileSync, mkdirSync } from "fs";
import { join } from "path";
import { homedir } from "os";

export interface ConfigData {
  activeHost: string;
  activeProject?: string;
  activeEnvironment?: string;
}

export interface AuthData {
  token?: string;
  user?: {
    id: number;
    name: string;
    email: string;
  };
}

const configDir = join(homedir(), ".config", "keysha");
const configFile = join(configDir, "config.json");
const authFile = join(configDir, "auth.json");

function ensureConfigDir() {
  if (!existsSync(configDir)) {
    mkdirSync(configDir, { recursive: true });
  }
}

export function loadConfig(): ConfigData {
  ensureConfigDir();
  if (existsSync(configFile)) {
    try {
      return JSON.parse(readFileSync(configFile, "utf8"));
    } catch {}
  }
  return {
    activeHost: process.env.KEYSHA_HOST || "http://localhost:8000",
  };
}

export function saveConfig(data: Partial<ConfigData>) {
  ensureConfigDir();
  const current = loadConfig();
  const updated = { ...current, ...data };
  writeFileSync(configFile, JSON.stringify(updated, null, 2), "utf8");
}

export function loadAuth(): AuthData {
  ensureConfigDir();
  if (existsSync(authFile)) {
    try {
      return JSON.parse(readFileSync(authFile, "utf8"));
    } catch {}
  }
  return {};
}

export function saveAuth(data: AuthData) {
  ensureConfigDir();
  writeFileSync(authFile, JSON.stringify(data, null, 2), "utf8");
}

export function clearAuth() {
  ensureConfigDir();
  if (existsSync(authFile)) {
    writeFileSync(authFile, "{}", "utf8");
  }
}
