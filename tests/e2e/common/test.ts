import { test as base, expect, Page, Browser, BrowserContext } from "@playwright/test";
import { BASE_PATH } from "./fixtures";

export { expect };

// Prepend BASE_PATH to root-relative URLs. Absolute URLs, protocol-relative
// URLs (//host/path), and relative-path inputs pass through untouched.
function prefixed(url: string): string {
    return BASE_PATH && url.startsWith("/") && !url.startsWith("//")
        ? BASE_PATH + url
        : url;
}

function patchPage(page: Page): void {
    // Guard: only patch once per page instance.
    if ((page as any)._tikiPatched) return;
    (page as any)._tikiPatched = true;

    if (!BASE_PATH) return;

    const _goto = page.goto.bind(page);
    // @ts-ignore — intentional override to support TIKI_BASE_PATH subpath installs
    page.goto = (url: string, opts?: Parameters<Page["goto"]>[1]) =>
        _goto(prefixed(url), opts);

    const req = page.request;
    const _post = req.post.bind(req);
    const _get = req.get.bind(req);
    // @ts-ignore
    req.post = (url: string, opts?: Parameters<typeof req.post>[1]) =>
        _post(prefixed(url), opts);
    // @ts-ignore
    req.get = (url: string, opts?: Parameters<typeof req.get>[1]) =>
        _get(prefixed(url), opts);
}

function patchBrowser(browser: Browser): void {
    if ((browser as any)._tikiPatched) return;
    (browser as any)._tikiPatched = true;

    if (!BASE_PATH) return;

    const _newPage = browser.newPage.bind(browser);
    // @ts-ignore
    browser.newPage = async (opts?: Parameters<Browser["newPage"]>[0]) => {
        const page = await _newPage(opts);
        patchPage(page);
        return page;
    };

    const _newContext = browser.newContext.bind(browser);
    // @ts-ignore
    browser.newContext = async (opts?: Parameters<Browser["newContext"]>[0]) => {
        const ctx: BrowserContext = await _newContext(opts);
        const _ctxNewPage = ctx.newPage.bind(ctx);
        // @ts-ignore
        ctx.newPage = async (ctxOpts?: Parameters<BrowserContext["newPage"]>[0]) => {
            const page = await _ctxNewPage(ctxOpts);
            patchPage(page);
            return page;
        };
        return ctx;
    };
}

export const test = base.extend<
    Record<string, never>,
    { browser: Browser }
>({
    browser: [
        async ({ browser }, use) => {
            patchBrowser(browser);
            await use(browser);
        },
        { scope: "worker" },
    ],
    page: async ({ page }, use) => {
        patchPage(page);
        await use(page);
    },
});
