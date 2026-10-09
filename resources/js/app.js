function closeNavigationMenu(toggle, menu) {
    if (!toggle || !menu) {
        return;
    }

    toggle.setAttribute("aria-expanded", "false");
    toggle.setAttribute("aria-label", "Open navigation menu");

    menu.classList.add("hidden");

    toggle.querySelector("[data-menu-icon-open]")?.classList.remove("hidden");
    toggle.querySelector("[data-menu-icon-close]")?.classList.add("hidden");
}

document.addEventListener("click", (event) => {
    const toggle = event.target.closest("[data-menu-toggle]");

    if (toggle) {
        const menu = document.getElementById(
            toggle.getAttribute("aria-controls"),
        );

        if (!menu) {
            return;
        }

        const isExpanded = toggle.getAttribute("aria-expanded") === "true";

        if (isExpanded) {
            closeNavigationMenu(toggle, menu);
            return;
        }

        toggle.setAttribute("aria-expanded", "true");
        toggle.setAttribute("aria-label", "Close navigation menu");

        menu.classList.remove("hidden");

        toggle.querySelector("[data-menu-icon-open]")?.classList.add("hidden");
        toggle
            .querySelector("[data-menu-icon-close]")
            ?.classList.remove("hidden");

        return;
    }

    const link = event.target.closest("#mobile-navigation a");

    if (link) {
        const menu = link.closest("#mobile-navigation");
        const header = menu?.closest("header");
        const menuToggle = header?.querySelector("[data-menu-toggle]");

        closeNavigationMenu(menuToggle, menu);
    }

    const toggleButton = document.querySelector("[data-menu-toggle]");
    const menu = toggleButton
        ? document.getElementById(toggleButton.getAttribute("aria-controls"))
        : null;

    if (
        toggleButton?.getAttribute("aria-expanded") === "true" &&
        menu &&
        !menu.contains(event.target) &&
        !toggleButton.contains(event.target)
    ) {
        closeNavigationMenu(toggleButton, menu);
    }
});

document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") {
        return;
    }

    const toggle = document.querySelector("[data-menu-toggle]");
    const menu = toggle
        ? document.getElementById(toggle.getAttribute("aria-controls"))
        : null;

    if (toggle?.getAttribute("aria-expanded") === "true") {
        closeNavigationMenu(toggle, menu);
        toggle.focus();
    }
});
