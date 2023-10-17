/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */
/**
 * Creates a promise with the resolve and reject function outside of it, useful for tasks that may complete at any time.
 * Based on MIT licensed https://github.com/arikw/flat-promise, with typings added by gzuidhof.
 * @param executor
 */
function flatPromise(executor) {
    let resolve;
    let reject;
    const promise = new Promise((res, rej) => {
        // Is this any cast necessary?
        resolve = res;
        reject = rej;
    });
    if (executor) {
        // This is actually valid.. as in the spec the function above the Promise gets executed immediately.
        executor(resolve, reject);
    }
    return { promise, resolve, reject };
}

/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/. */
function getDefaultAllowAttributeValue() {
    var _a;
    // Extract Firefox version.. We know for sure in FF 91 and lower the `allow` is more limited than Chrome's
    // and it spams the console with warnings if we're not careful.. TODO: find a way to disable those warnings?
    const ffMatch = navigator.userAgent.match(/Firefox\/(\d+)/);
    if (ffMatch !== null) {
        const ffVersion = parseInt(ffMatch[2] || "0");
        if (!isNaN(ffVersion) && ffVersion <= 91) {
            return "camera;fullscreen;gamepad;geolocation;microphone;web-share";
        }
    }
    // excluded for all: display-capture, document-domain, encrypted-media
    const featurePolicyAllowedFeatures = ((_a = document.featurePolicy) === null || _a === void 0 ? void 0 : _a.allowedFeatures) && document.featurePolicy.allowedFeatures();
    if (featurePolicyAllowedFeatures) {
        return featurePolicyAllowedFeatures
            .filter((x) => ["display-capture", "document-domain", "encrypted-media"].indexOf(x) === -1)
            .join(";");
    }
    else {
        // excluded because latest chrome doesn't know about it: gamepad, ambient-light-sensor, battery, execution-while-not-rendered, execution-while-out-of-viewport, navigation-override
        return `camera;fullscreen;geolocation;microphone;web-share;cross-origin-isolated;accelerometer;autoplay;gyroscope;magnetometer;midi;payment;picture-in-picture;publickey-credentials-get;sync-xhr;usb;screen-wake-lock;xr-spatial-tracking`;
    }
}
function loadDefaultSettings(opts, el) {
    var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m, _o, _p;
    return {
        iFrame: opts.iFrame || null,
        src: (_c = (_b = (_a = opts.src) !== null && _a !== void 0 ? _a : el.getAttribute("src")) !== null && _b !== void 0 ? _b : window.starboardEmbedIFrameSrc) !== null && _c !== void 0 ? _c : "https://cdn.starboard.gg/npm/starboard-notebook@0.13.2/dist/index.html",
        baseUrl: opts.baseUrl || el.dataset["baseUrl"] || undefined,
        autoResize: (_d = opts.autoResize) !== null && _d !== void 0 ? _d : true,
        sandbox: (_f = (_e = opts.sandbox) !== null && _e !== void 0 ? _e : el.getAttribute("sandbox")) !== null && _f !== void 0 ? _f : "allow-scripts allow-modals allow-same-origin allow-pointer-lock allow-top-navigation-by-user-activation allow-forms allow-downloads",
        allow: (_h = (_g = opts.allow) !== null && _g !== void 0 ? _g : el.getAttribute("allow")) !== null && _h !== void 0 ? _h : getDefaultAllowAttributeValue(),
        onNotebookReadySignalMessage: (_j = opts.onNotebookReadySignalMessage) !== null && _j !== void 0 ? _j : function () { },
        onContentUpdateMessage: (_k = opts.onContentUpdateMessage) !== null && _k !== void 0 ? _k : function () { },
        onSaveMessage: (_l = opts.onSaveMessage) !== null && _l !== void 0 ? _l : function () { },
        onMessage: (_m = opts.onMessage) !== null && _m !== void 0 ? _m : function () { },
        onUnsavedChangesStatusChange: (_o = opts.onUnsavedChangesStatusChange) !== null && _o !== void 0 ? _o : function () { },
        notebookContent: opts.notebookContent,
        preventNavigationWithUnsavedChanges: (_p = opts.preventNavigationWithUnsavedChanges) !== null && _p !== void 0 ? _p : false,
    };
}
class StarboardEmbed extends HTMLElement {
    constructor(opts = {}) {
        super();
        this.notebookContent = "";
        this.lastSavedNotebookContent = "";
        /** Has unsaved changes */
        this.dirty = false;
        // The version of starboard-wrap
        this.version = "0.4.1";
        this.hasReceivedReadyMessage = flatPromise();
        this.constructorOptions = opts;
        this.style.display = "block";
        if (this.constructorOptions.iFrame) {
            this.iFrame = this.constructorOptions.iFrame;
        }
        else {
            this.iFrame = this.querySelector("iframe");
        }
    }
    connectedCallback() {
        if (!this.iFrame) {
            // Find iframe element child, and otherwise create one.
            this.iFrame = this.querySelector("iframe");
            if (!this.iFrame) {
                this.iFrame = document.createElement("iframe");
                this.appendChild(this.iFrame);
            }
        }
        this.iFrame.style.width = "100%";
        this.options = loadDefaultSettings(this.constructorOptions, this.iFrame);
        if (this.options.preventNavigationWithUnsavedChanges) {
            this.unsavedChangesWarningFunction = (e) => {
                if (this.dirty) {
                    e.preventDefault();
                    e.returnValue = "";
                }
            };
            window.addEventListener("beforeunload", this.unsavedChangesWarningFunction);
        }
        if (!this.options.notebookContent) {
            const scriptEl = this.querySelector("script");
            if (scriptEl) {
                this.options.notebookContent = scriptEl.innerText;
            }
        }
        this.iFrame.sandbox.value = this.options.sandbox;
        if (!this.iFrame.allow) {
            this.iFrame.allow = this.options.allow;
        }
        // Without this check it will reload the page
        if (this.iFrame.src !== this.options.src) {
            this.iFrame.src = this.options.src;
        }
        this.iFrame.frameBorder = "0";
        this.iFrameMessageHandler = async (ev) => {
            var _a;
            if (ev.source === null || ev.source !== ((_a = this.iFrame) === null || _a === void 0 ? void 0 : _a.contentWindow))
                return;
            const options = this.options;
            if (!options)
                return;
            const checkOrigin = [new URL(options.src, location.origin).origin];
            if (!checkOrigin.includes(ev.origin))
                return;
            if (!ev.data)
                return;
            const msg = ev.data;
            // @ts-ignore // TODO: Remove this ts-ignore once the typings have been updated
            if (msg.type === "NOTEBOOK_RESIZE_REQUEST") {
                const iFrame = this.iFrame;
                if (options.autoResize && iFrame) {
                    iFrame.setAttribute("scrolling", "no");
                    // Todo: make the width super stable as well
                    // iFrame.style.width = `${ev.data.payload.width}px`;
                    iFrame.style.height = `${ev.data.payload.height + 2}px`; // Not sure why I need + 2
                }
            }
            else if (msg.type === "NOTEBOOK_READY_SIGNAL") {
                if (options.notebookContent) {
                    const content = await options.notebookContent;
                    this.notebookContent = content;
                    this.lastSavedNotebookContent = this.notebookContent;
                    this.sendMessage({
                        type: "NOTEBOOK_SET_INIT_DATA",
                        payload: { content, baseUrl: options.baseUrl },
                    });
                }
                else {
                    this.notebookContent = msg.payload.content;
                    this.lastSavedNotebookContent = this.notebookContent;
                }
                this.hasReceivedReadyMessage.resolve(msg.payload);
                options.onNotebookReadySignalMessage(msg.payload);
            }
            else if (msg.type === "NOTEBOOK_CONTENT_UPDATE") {
                this.notebookContent = msg.payload.content;
                this.updateDirty();
                options.onContentUpdateMessage(msg.payload);
            }
            else if (msg.type === "NOTEBOOK_SAVE_REQUEST") {
                this.notebookContent = msg.payload.content;
                this.updateDirty();
                // Make it a promise regardless of return value of the function.
                const r = Promise.resolve(options.onSaveMessage(msg.payload));
                r.then((ret) => {
                    if (ret === true) {
                        this.lastSavedNotebookContent = msg.payload.content;
                        this.updateDirty();
                    }
                });
            }
            options.onMessage(msg);
        };
        window.addEventListener("message", this.iFrameMessageHandler);
    }
    sendMessage(message) {
        // Sending messages before the iframe leads to messages being lost, which can happen when the iframe loads slowly.
        this.hasReceivedReadyMessage.promise.then(() => { var _a, _b; return (_b = (_a = this.iFrame) === null || _a === void 0 ? void 0 : _a.contentWindow) === null || _b === void 0 ? void 0 : _b.postMessage(message, "*"); });
    }
    /**
     * Tell the embed a save has been made with the given content so it can update it's "dirty" status.
     * If no content is supplied, the current content is assumed to be the just saved content.
     */
    setSaved(content) {
        if (content === undefined) {
            content = this.notebookContent;
        }
        this.lastSavedNotebookContent = content;
        this.updateDirty();
    }
    updateDirty() {
        var _a;
        const priorDirtyState = this.dirty;
        this.dirty = this.lastSavedNotebookContent !== this.notebookContent;
        if (this.dirty !== priorDirtyState) {
            (_a = this.options) === null || _a === void 0 ? void 0 : _a.onUnsavedChangesStatusChange(this.dirty);
        }
    }
    sendCustomMessage(message) {
        this.sendMessage(message);
    }
    dispose() {
        if (this.iFrameMessageHandler) {
            window.removeEventListener("message", this.iFrameMessageHandler);
        }
        if (this.unsavedChangesWarningFunction) {
            window.removeEventListener("beforeunload", this.unsavedChangesWarningFunction);
        }
    }
}
customElements.define("starboard-embed", StarboardEmbed);

export { StarboardEmbed };
