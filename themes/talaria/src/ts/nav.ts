type NavElements = {
  palette: HTMLElement;
  openButtons: HTMLElement[];
  closeTargets: HTMLElement[];
  input: HTMLInputElement;
  items: HTMLElement[];
  groups: HTMLElement[];
  empty: HTMLElement | null;
};

function qsAll<T extends Element>(root: ParentNode, sel: string): T[] {
  return Array.from(root.querySelectorAll(sel)) as T[];
}

function bindNavPalette(): void {
  const palette = document.querySelector<HTMLElement>('[data-nav-palette]');
  const input = document.querySelector<HTMLInputElement>('[data-nav-filter]');
  if (!palette || !input) {
    return;
  }

  const els: NavElements = {
    palette,
    openButtons: qsAll<HTMLElement>(document, '[data-nav-open]'),
    closeTargets: qsAll<HTMLElement>(palette, '[data-nav-close]'),
    input,
    items: qsAll<HTMLElement>(palette, '[data-nav-item]'),
    groups: qsAll<HTMLElement>(palette, '[data-nav-group]'),
    empty: palette.querySelector<HTMLElement>('[data-nav-empty]'),
  };

  let activeIndex = -1;
  let open = false;

  const visibleItems = (): HTMLElement[] =>
    els.items.filter((item) => !item.hidden);

  const setActive = (index: number): void => {
    const items = visibleItems();
    els.items.forEach((item) => item.classList.remove('is-active'));
    if (items.length === 0) {
      activeIndex = -1;
      return;
    }
    activeIndex = ((index % items.length) + items.length) % items.length;
    const active = items[activeIndex];
    active?.classList.add('is-active');
    active?.querySelector<HTMLElement>('[data-nav-link]')?.scrollIntoView({
      block: 'nearest',
    });
  };

  const applyFilter = (): void => {
    const q = els.input.value.trim().toLowerCase();
    let visibleCount = 0;

    for (const item of els.items) {
      const hay = item.dataset.search ?? item.textContent?.toLowerCase() ?? '';
      const match = q === '' || hay.includes(q);
      item.hidden = !match;
      if (match) {
        visibleCount += 1;
      }
    }

    for (const group of els.groups) {
      const anyVisible = qsAll<HTMLElement>(group, '[data-nav-item]').some(
        (item) => !item.hidden,
      );
      group.hidden = !anyVisible;
    }

    if (els.empty) {
      els.empty.hidden = visibleCount > 0;
    }

    setActive(visibleCount > 0 ? 0 : -1);
  };

  const openPalette = (): void => {
    if (open) {
      return;
    }
    open = true;
    palette.hidden = false;
    document.body.classList.add('nav-palette-open');
    for (const btn of els.openButtons) {
      btn.setAttribute('aria-expanded', 'true');
    }
    els.input.value = '';
    applyFilter();
    queueMicrotask(() => els.input.focus());
  };

  const closePalette = (): void => {
    if (!open) {
      return;
    }
    open = false;
    palette.hidden = true;
    document.body.classList.remove('nav-palette-open');
    for (const btn of els.openButtons) {
      btn.setAttribute('aria-expanded', 'false');
    }
    activeIndex = -1;
    els.items.forEach((item) => item.classList.remove('is-active'));
  };

  const activateCurrent = (): void => {
    const items = visibleItems();
    const item = items[activeIndex] ?? items[0];
    const link = item?.querySelector<HTMLAnchorElement>('[data-nav-link]');
    if (link) {
      closePalette();
      window.location.assign(link.href);
    }
  };

  for (const btn of els.openButtons) {
    btn.addEventListener('click', (event) => {
      event.preventDefault();
      openPalette();
    });
  }

  for (const target of els.closeTargets) {
    target.addEventListener('click', () => closePalette());
  }

  els.input.addEventListener('input', () => applyFilter());

  document.addEventListener('keydown', (event) => {
    const metaK =
      (event.key === 'k' || event.key === 'K') &&
      (event.metaKey || event.ctrlKey);
    if (metaK) {
      event.preventDefault();
      if (open) {
        closePalette();
      } else {
        openPalette();
      }
      return;
    }

    if (!open) {
      return;
    }

    if (event.key === 'Escape') {
      event.preventDefault();
      closePalette();
      return;
    }

    if (event.key === 'ArrowDown') {
      event.preventDefault();
      setActive(activeIndex + 1);
      return;
    }

    if (event.key === 'ArrowUp') {
      event.preventDefault();
      setActive(activeIndex <= 0 ? visibleItems().length - 1 : activeIndex - 1);
      return;
    }

    if (event.key === 'Enter') {
      event.preventDefault();
      activateCurrent();
    }
  });

  // Update ⌘K hint on non-Mac.
  const isApple = /Mac|iPhone|iPad|iPod/.test(navigator.platform);
  if (!isApple) {
    qsAll<HTMLElement>(document, '.site-nav-trigger__kbd').forEach((kbd) => {
      kbd.textContent = 'Ctrl K';
    });
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bindNavPalette);
} else {
  bindNavPalette();
}
