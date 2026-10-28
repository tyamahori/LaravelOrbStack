import { expect, test } from '@playwright/test';

// Taskfile sets LARAVELORBSTACK_DOMAIN to <project>.local in a worktree (README「worktree ごとの環境」).
const suffix = process.env.LARAVELORBSTACK_DOMAIN || 'local';

for (const { host, runtime } of [
    { host: 'apachephp', runtime: 'Apache mod_php' },
    { host: 'frankenphp', runtime: 'FrankenPHP' },
]) {
    test(`${host} top page opens in Chromium`, async ({ page }) => {
        const base = `https://${host}.${suffix}`;

        // goto rejects when the domain does not resolve or its certificate is not trusted.
        const response = await page.goto(`${base}/`);

        expect(response?.status()).toBe(200);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('LaravelOrbStack');
        // The page marks the runtime that served it: the balancer must route each domain to its own.
        await expect(page.getByRole('list', { name: '実行環境' }).locator('.is-active strong')).toHaveText(runtime);
        // The link is built by route(), so it must stay on the domain and scheme the browser came in on.
        await page.getByRole('link', { name: 'メモのサンプル' }).click();
        await expect(page).toHaveURL(`${base}/memos`);
    });
}
