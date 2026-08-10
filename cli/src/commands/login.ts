import { apiRequest } from "../api";
import { saveAuth, loadConfig } from "../config";

export async function loginCommand() {
  console.log("\n🔑 Keysha Device Authorization\n");

  try {
    const res = await apiRequest("/auth/device/code", {
      method: "POST",
      body: JSON.stringify({
        device_name: "TypeScript Bun CLI",
      }),
    });

    console.log(`Code:              ${res.user_code}`);
    console.log(`Verification URL:  ${res.verification_uri}?code=${res.user_code}\n`);
    console.log("Opening browser or waiting for approval...");

    // Poll for authorization token
    const pollInterval = (res.interval || 2) * 1000;
    const expiresAt = Date.now() + (res.expires_in || 600) * 1000;

    while (Date.now() < expiresAt) {
      await new Promise((r) => setTimeout(r, pollInterval));

      try {
        const tokenRes = await apiRequest("/auth/device/token", {
          method: "POST",
          body: JSON.stringify({
            device_code: res.device_code,
          }),
        });

        if (tokenRes.access_token) {
          saveAuth({
            token: tokenRes.access_token,
            user: tokenRes.user,
          });

          console.log(`\n✓ Successfully authenticated as ${tokenRes.user.email}!`);
          return;
        }
      } catch (err: any) {
        if (err.message.includes("authorization_pending")) {
          process.stdout.write(".");
          continue;
        }
        if (err.message.includes("access_denied")) {
          console.error("\n❌ Authorization denied by user.");
          return;
        }
      }
    }

    console.error("\n❌ Device authorization timed out.");
  } catch (err: any) {
    console.error(`\n❌ Login failed: ${err.message}`);
  }
}
