export function formatPublishedAt(iso: string): string {
  if (!iso) {
    return '';
  }

  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) {
    return iso;
  }

  return new Intl.DateTimeFormat(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(date);
}

export function detailUrl(baseUrl: string, slug: string): string {
  const base = baseUrl.replace(/\/+$/, '');
  return `${base}/${encodeURIComponent(slug)}`;
}
