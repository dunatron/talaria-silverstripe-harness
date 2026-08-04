/**
 * Parse a JSON value from a data-* attribute.
 * Returns null when missing/invalid (never throws).
 */
export function parseDataJson<T>(element: HTMLElement, attribute: string): T | null {
  const raw = element.getAttribute(attribute);
  if (raw === null || raw === '') {
    return null;
  }

  try {
    return JSON.parse(raw) as T;
  } catch {
    return null;
  }
}

export function clearElement(element: HTMLElement): void {
  while (element.firstChild) {
    element.removeChild(element.firstChild);
  }
}

export function el<K extends keyof HTMLElementTagNameMap>(
  tag: K,
  options: {
    className?: string;
    text?: string;
    html?: string;
    attrs?: Record<string, string>;
    children?: Array<Node | null | undefined>;
  } = {},
): HTMLElementTagNameMap[K] {
  const node = document.createElement(tag);
  if (options.className) {
    node.className = options.className;
  }
  if (options.text !== undefined) {
    node.textContent = options.text;
  }
  if (options.html !== undefined) {
    node.innerHTML = options.html;
  }
  if (options.attrs) {
    for (const [key, value] of Object.entries(options.attrs)) {
      node.setAttribute(key, value);
    }
  }
  if (options.children) {
    for (const child of options.children) {
      if (child) {
        node.appendChild(child);
      }
    }
  }
  return node;
}
