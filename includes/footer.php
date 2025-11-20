            </div>
        </main>
    </div>
    <footer>
        <p>&copy; <?= date('Y') ?> Himalaya Darshan College - Document Portal</p>
    </footer>
    <script src="/assets/js/main.js"></script>
    <script>
        // Ensure alerts auto-hide even if injected after DOMContentLoaded
        (function() {
            const HIDE_DELAY = 5000;

            function hideAlert(el) {
                if (!el) return;
                // Avoid double-scheduling
                if (el.__hideScheduled) return;
                el.__hideScheduled = true;
                setTimeout(() => {
                    el.style.transition = 'opacity 0.3s, transform 0.3s';
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(-6px)';
                    setTimeout(() => { try { el.remove(); } catch(e){} }, 350);
                }, HIDE_DELAY);
            }

            // Hide existing alerts
            document.querySelectorAll('.alert').forEach(hideAlert);

            // Watch for future alerts added to the DOM
            const mo = new MutationObserver(mutations => {
                for (const m of mutations) {
                    for (const node of m.addedNodes) {
                        if (!(node instanceof HTMLElement)) continue;
                        if (node.classList && node.classList.contains('alert')) hideAlert(node);
                        // if container contains alerts
                        node.querySelectorAll && node.querySelectorAll('.alert').forEach(hideAlert);
                    }
                }
            });
            mo.observe(document.body, { childList: true, subtree: true });
        })();
    </script>
</body>
</html>

