import type { ScopedTalaria } from '@newtalaria/browser';
import { clearElement, el } from '../lib/dom';
import type { BlogListProps } from '../types/blog';
import { detailUrl, formatPublishedAt } from './format';

export async function renderBlogList(
  mount: HTMLElement,
  props: BlogListProps,
  blogLogger: ScopedTalaria,
): Promise<void> {
  clearElement(mount);

  if (props.posts.length === 0) {
    mount.appendChild(
      el('div', {
        className: 'blog-empty',
        text: 'No posts found in the local JSON fixture.',
      }),
    );
    await blogLogger.warn('Blog list is empty');
    return;
  }

  const list = el('div', { className: 'blog-list' });

  for (const post of props.posts) {
    const href = detailUrl(props.detailBaseUrl, post.slug);
    const tags = el('ul', {
      className: 'blog-tags',
      children: post.tags.map((tag) => el('li', { className: 'blog-tag', text: tag })),
    });

    const card = el('a', {
      className: 'blog-card',
      attrs: { href },
      children: [
        el('h2', { className: 'blog-card__title', text: post.title }),
        el('p', { className: 'blog-card__excerpt', text: post.excerpt }),
        el('div', {
          className: 'blog-card__meta',
          children: [
            el('span', { text: post.author }),
            el('span', { text: formatPublishedAt(post.publishedAt) }),
          ],
        }),
        tags,
      ],
    });

    list.appendChild(card);
  }

  mount.appendChild(list);
  await blogLogger.info('Blog list rendered', {
    extra: { count: props.posts.length },
  });
}
