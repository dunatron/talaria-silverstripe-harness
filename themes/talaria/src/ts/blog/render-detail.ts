import type { ScopedTalaria } from '@newtalaria/browser';
import { clearElement, el } from '../lib/dom';
import type { BlogDetailProps } from '../types/blog';
import { formatPublishedAt } from './format';

export async function renderBlogDetail(
  mount: HTMLElement,
  props: BlogDetailProps,
  blogLogger: ScopedTalaria,
): Promise<void> {
  clearElement(mount);

  const back = el('a', {
    className: 'blog-detail__back',
    attrs: { href: props.listUrl },
    text: '← Back to blog',
  });

  if (!props.found || !props.post) {
    mount.appendChild(back);
    mount.appendChild(
      el('div', {
        className: 'blog-not-found',
        children: [
          el('h1', { text: 'Post not found' }),
          el('p', {
            text: props.slug
              ? `No post matches “${props.slug}” in the local JSON fixture.`
              : 'Open a post from the blog list.',
          }),
        ],
      }),
    );
    await blogLogger.warn('Blog post not found', {
      tags: { component: 'blog-detail' },
      extra: { slug: props.slug },
    });
    return;
  }

  const { post } = props;

  mount.appendChild(back);
  mount.appendChild(el('h1', { className: 'blog-detail__title', text: post.title }));
  mount.appendChild(
    el('div', {
      className: 'blog-detail__meta',
      children: [
        el('span', { text: post.author }),
        el('span', { text: formatPublishedAt(post.publishedAt) }),
      ],
    }),
  );

  if (post.tags.length > 0) {
    mount.appendChild(
      el('ul', {
        className: 'blog-tags',
        children: post.tags.map((tag) => el('li', { className: 'blog-tag', text: tag })),
      }),
    );
  }

  mount.appendChild(
    el('div', {
      className: 'blog-detail__body',
      html: post.body,
    }),
  );

  await blogLogger.info('Blog detail rendered', {
    tags: { component: 'blog-detail' },
    extra: { slug: post.slug, title: post.title },
  });
}
