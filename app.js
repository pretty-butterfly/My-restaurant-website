document.documentElement.classList.add("js-enabled");

const sidebar = document.querySelector(".sidebar");
if (sidebar) {
    const menu = sidebar.querySelector(".sidebar-menu");
    if (menu) {
        if (!menu.id) {
            menu.id = "site-navigation";
        }

        const toggle = document.createElement("button");
        toggle.className = "sidebar-toggle";
        toggle.type = "button";
        toggle.setAttribute("aria-controls", menu.id);
        toggle.setAttribute("aria-expanded", "false");
        toggle.setAttribute("aria-label", "Open navigation menu");
        toggle.innerHTML = "<span></span><span></span><span></span>";
        sidebar.insertBefore(toggle, menu);

        toggle.addEventListener("click", () => {
            const isOpen = sidebar.classList.toggle("sidebar-open");
            toggle.setAttribute("aria-expanded", String(isOpen));
            toggle.setAttribute("aria-label", isOpen ? "Close navigation menu" : "Open navigation menu");
        });

        menu.addEventListener("click", (event) => {
            if (event.target.closest("a") && window.matchMedia("(max-width: 768px)").matches) {
                sidebar.classList.remove("sidebar-open");
                toggle.setAttribute("aria-expanded", "false");
                toggle.setAttribute("aria-label", "Open navigation menu");
            }
        });
    }
}

const landingNavigation = document.querySelector(".navbar");
if (landingNavigation) {
    const links = landingNavigation.querySelector(".nav-links");
    if (links) {
        if (!links.id) {
            links.id = "landing-navigation";
        }

        const toggle = document.createElement("button");
        toggle.className = "landing-nav-toggle";
        toggle.type = "button";
        toggle.setAttribute("aria-controls", links.id);
        toggle.setAttribute("aria-expanded", "false");
        toggle.setAttribute("aria-label", "Open navigation menu");
        toggle.innerHTML = "<span></span><span></span><span></span>";
        landingNavigation.insertBefore(toggle, links);

        toggle.addEventListener("click", () => {
            const isOpen = landingNavigation.classList.toggle("landing-nav-open");
            toggle.setAttribute("aria-expanded", String(isOpen));
            toggle.setAttribute("aria-label", isOpen ? "Close navigation menu" : "Open navigation menu");
        });

        links.addEventListener("click", (event) => {
            if (event.target.closest("a")) {
                landingNavigation.classList.remove("landing-nav-open");
                toggle.setAttribute("aria-expanded", "false");
                toggle.setAttribute("aria-label", "Open navigation menu");
            }
        });
    }
}

const passwordInputs = document.querySelectorAll('.auth-container input[type="password"]');
passwordInputs.forEach((input) => {
    const wrapper = document.createElement("div");
    wrapper.className = "password-input-wrap";
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    const toggle = document.createElement("button");
    toggle.className = "password-toggle";
    toggle.type = "button";
    toggle.textContent = "Show";
    toggle.setAttribute("aria-label", "Show password");
    wrapper.appendChild(toggle);

    toggle.addEventListener("click", () => {
        const shouldShow = input.type === "password";
        input.type = shouldShow ? "text" : "password";
        toggle.textContent = shouldShow ? "Hide" : "Show";
        toggle.setAttribute("aria-label", shouldShow ? "Hide password" : "Show password");
    });
});

const interactiveCards = document.querySelectorAll(
    ".dashboard-card, .feature-card, .food-card, .auth-container, .profile-form, .booking-form, .tables-form, .menu-item, .review-form, .payment-form, .payment-success, .payment-empty-state, .review-thank-you"
);

interactiveCards.forEach((card) => {
    card.classList.add("interactive-card");

    card.addEventListener("pointermove", (event) => {
        const rect = card.getBoundingClientRect();
        const x = ((event.clientX - rect.left) / rect.width) * 100;
        const y = ((event.clientY - rect.top) / rect.height) * 100;

        card.style.setProperty("--pointer-x", `${x}%`);
        card.style.setProperty("--pointer-y", `${y}%`);
        card.style.transform = `perspective(1000px) rotateX(${(50 - y) / 18}deg) rotateY(${(x - 50) / 18}deg) translateY(-4px)`;
        card.style.boxShadow = "0 24px 40px rgba(126, 64, 32, 0.16)";
    });

    card.addEventListener("pointerleave", () => {
        card.style.transform = "";
        card.style.boxShadow = "";
    });
});

const revealTargets = document.querySelectorAll(
    ".welcome, .dashboard-card, .auth-container, .feature-card, .food-card, .landing-cta-content, .profile-form, .booking-form, .tables-form, .menu-section, .menu-occasion-section, .payment-form, .payment-success, .payment-empty-state, .review-form, .review-thank-you"
);

if ("IntersectionObserver" in window && !window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add("is-visible");
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08 });

    revealTargets.forEach((element) => {
        element.classList.add("reveal-on-scroll");
        revealObserver.observe(element);
    });
} else {
    revealTargets.forEach((element) => element.classList.add("is-visible"));
}

const progress = document.createElement("div");
progress.className = "reading-progress";
progress.setAttribute("aria-hidden", "true");
document.body.appendChild(progress);

let progressFrame = 0;
window.addEventListener("scroll", () => {
    if (progressFrame) {
        return;
    }

    progressFrame = window.requestAnimationFrame(() => {
        const scrollableHeight = document.documentElement.scrollHeight - window.innerHeight;
        const percentage = scrollableHeight > 0 ? (window.scrollY / scrollableHeight) * 100 : 0;
        progress.style.transform = `scaleX(${percentage / 100})`;
        progressFrame = 0;
    });
}, { passive: true });
