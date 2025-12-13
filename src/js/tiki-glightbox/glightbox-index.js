import GLightbox from "glightbox";

export default function initGlightbox(config) {
    return function applyGlightbox({ preload = true } = {}) {
        const counterHTML = `<p data-counter class="gcounter"></p>`;
        let slideshowInterval = null;
        let elementsLength = 0;

        function escapeSelector(selector) {
            return selector.replace(/[!"#$%&'()*+,./:;<=>?@[\\\]^`{|}~]/g, "\\$&");
        }

        function setAttrInlineMedia(selector) {
            document.querySelectorAll(selector).forEach((el) => {
                el.setAttribute("data-width", el.getAttribute("data-box-width") ?? "100%");
                el.setAttribute("data-height", el.getAttribute("data-box-height") ?? "auto");
            });
        }

        function addCounter(modal) {
            const closeBtn = modal.querySelector(".gclose");
            if (!closeBtn || modal.querySelector("[data-counter]")) return;
            closeBtn.insertAdjacentHTML("beforebegin", counterHTML);
        }

        function updateCounter(modal, index) {
            const counter = modal.querySelector("[data-counter]");
            if (counter) {
                const current = index + 1;
                const total = elementsLength;
                counter.dataset.counter = `${current} / ${total}`;
                counter.textContent = `${current} / ${total}`;
            }
        }

        function removeCounter(modal) {
            const counter = modal.querySelector(".gcounter");
            if (counter) {
                counter.remove();
            }
        }

        function runCounter(lightbox, prev, current) {
            const { modal, index } = lightbox;
            if (!modal) return;

            if ([prev, current].includes(undefined)) {
                addCounter(modal);
                modal.querySelector(".gclose")?.addEventListener("click", () => removeCounter(modal), { once: true });
            }

            updateCounter(modal, index);
        }

        function setupTitles(selector) {
            // titles (for captions):
            // but prefer the title from title attribute of a wrapped image if any (in all columns)
            document.querySelectorAll(`${selector} img[title]`).forEach((img) => {
                const parent = img.closest("a");
                parent?.setAttribute("data-title", img.getAttribute("title"));
            });
            // by default get title from the title attribute of the link (in all columns)
            document.querySelectorAll(`${selector}[title]`).forEach((link) => {
                if (!link.hasAttribute("data-title")) {
                    link.setAttribute("data-title", link.getAttribute("title"));
                }
            });
        }

        /* Shadowbox params compatibility extracted using regexp functions */
        function setupShadowboxParams(selector) {
            // data-box attributes containing title, width, height param overrides title attribute of the link (shadowbox compatible)
            document.querySelectorAll(`#col1 ${selector}`).forEach((link) => {
                const box = link.dataset.box || "";
                const extract = (key) => {
                    const re = new RegExp(`${key}=([^;"\\]]+)`, "i");
                    const match = box.match(re);
                    return match ? match[1] : null;
                };
                ["title", "height", "width", "type"].forEach((key) => {
                    const val = extract(key);
                    if (val) link.dataset[key] = val;
                });
            });
        }

        function processElements({ selector, enforceUnique = true, defaultType = null, width = null, height = null, slideshowSpeed = null } = {}) {
            const elements = document.querySelectorAll(selector);
            elements.forEach((el, index) => {
                if (enforceUnique && !el.dataset.gallery) {
                    el.dataset.gallery = el.dataset.box || `gallery-${index}`;
                }
                if (!el.dataset.type && defaultType) {
                    el.dataset.type = defaultType;
                }
                if (!el.dataset.width && width) {
                    el.dataset.width = width;
                }
                if (!el.dataset.height && height) {
                    el.dataset.height = height;
                }
                if (slideshowSpeed !== null && slideshowSpeed !== undefined) {
                    el.dataset.slideshowSpeed = slideshowSpeed;
                }
            });
        }

        function initGlightbox() {
            // now, first let suppose that we want to display images in ColorBox by default:
            const elementConfigs = [
                // data-box attributes containing slideshow (this one must be without #col1)
                { selector: "a[data-box^='shadowbox[colorbox']", defaultType: "image" },
                {
                    // this matches data-box attributes containing type=img or no type= specified
                    selector: "a[data-box*='box'][data-box*='type=img'], a[data-box*='box']:not([data-box*='type=']):not([data-is-text])",
                    defaultType: "image",
                },
                // these are the defaults matching all *box links which are not obviously links to images...
                // (if we need to support more, add here... otherwise it is possible to override with type=iframe in data-box attribute of a link)
                //  (from here one to speed it up, matches any link in #col1 only - the main content column)
                {
                    selector:
                        "#col1 a[data-box*='box']:not([data-box*='type=img']):not([href*='display']):not([href*='preview']):not([href*='thumb']):not([data-box*='slideshow']):not([href*='image']):not([href$='.jpg']):not([href$='.jpeg']):not([href$='.png']):not([href$='.gif'])",
                    defaultType: "iframe",
                    width: "95%",
                    height: "95%",
                },
                {
                    // hrefs starting with ftp(s)
                    selector: "#col1 a[data-box*='box'][href^='ftp://'], #col1 a[data-box*='box'][href^='ftps://']",
                    defaultType: "iframe",
                    width: "95%",
                    height: "95%",
                },
                // inline content: hrefs starting with #
                { selector: "#col1 a[data-box*='box'][href^='#']", width: "50%", height: "50%" },
                {
                    selector: "a[data-box*='box'][data-box*='slideshow']",
                    defaultType: "image",
                    width: "100%",
                    height: "100%",
                    slideshowSpeed: 3500,
                },
                // data-box attributes with type=iframe (if someone needs to override anything above)
                { selector: "#col1 a[data-box*='box'][data-box*='type=iframe']", defaultType: "iframe" },
                { selector: "a[rel*='box'][rel*='type=img'], a[rel*='box']:not([rel='type='])", defaultType: "image" },
            ];

            elementConfigs.forEach(processElements);
        }

        initGlightbox();
        const selector = "a[data-box*='box'], .cboxInlineMedia, a[rel*='box'], .colorbox";
        const glightbox = GLightbox({
            selector,
            touchNavigation: true,
            loop: true,
            preload,
            zoomable: true,
            openEffect: "zoom",
            closeEffect: "fade",
            plyr: config.plyr,
            callbacks: {
                onOpen: () => {
                    const activeSlide = glightbox.getActiveSlide();
                    const domElement = activeSlide?.node;
                    if (domElement) {
                        const speed = domElement.dataset.slideshowSpeed;
                        if (speed && !slideshowInterval) {
                            slideshowInterval = setInterval(() => {
                                glightbox.nextSlide();
                            }, Number(speed));
                        }
                    }
                },
                onClose: () => {
                    if (slideshowInterval) {
                        clearInterval(slideshowInterval);
                        slideshowInterval = null;
                    }
                },
            },
        });

        glightbox.on("open", () => {
            elementsLength = glightbox.elements.length;
            runCounter(glightbox);
        });

        glightbox.on("slide_before_change", ({ prev, current }) => {
            runCounter(glightbox, prev, current);
        });

        setupTitles(escapeSelector("a[data-box^='shadowbox[colorbox]']"));
        setupTitles(selector);
        setupShadowboxParams(selector);
        setAttrInlineMedia(".cboxInlineMedia");
    };
}
