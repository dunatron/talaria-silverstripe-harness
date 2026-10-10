import { Talaria, type TalariaInitOptions } from '@newtalaria/browser';
import { renderBlogDetail } from './blog/render-detail';
import { renderBlogList } from './blog/render-list';
import { parseDataJson } from './lib/dom';
import { isBlogDetailProps, isBlogListProps, type BlogProps } from './types/blog';

async function boot(): Promise<void> {
  const mount = document.getElementById('blog-app');
  if (!mount) {
    return;
  }

  const config = parseDataJson<TalariaInitOptions>(mount, 'data-talaria-config');
  if (!config) {
    throw new Error('Missing data-talaria-config — set TALARIA_BROWSER_DSN / TALARIA_API_KEY');
  }

  const props = parseDataJson<BlogProps>(mount, 'data-props');

  // Harness keeps info+ so successful render logs still ingest; production apps
  // should usually set minLevel: 'warning' in init options.
  Talaria.init({
    ...config,
    minLevel: config.minLevel ?? 'info',
    tags: {
      ...(config.tags ?? {}),
      ...(props?.entity ? { entity: props.entity } : {}),
    },
  });

  if (!props) {
    mount.textContent = 'Failed to load blog data.';
    await Talaria.error('Failed to parse blog data-props', {
      tags: { feature: 'blog', operation: 'hydrate' },
    });
    return;
  }

  const blogLogger = Talaria.logger({
    tags: {
      feature: 'blog',
      entity: props.entity,
      operation: props.view === 'list' ? 'list_posts' : 'view_post',
    },
  });

  try {
    if (isBlogListProps(props)) {
      await renderBlogList(mount, props, blogLogger);
      return;
    }
    if (isBlogDetailProps(props)) {
      await renderBlogDetail(mount, props, blogLogger);
      return;
    }
    mount.textContent = 'Unknown blog view.';
    await blogLogger.error('Unknown blog view in data-props', {
      tags: { component: 'blog-app' },
      extra: { view: (props as BlogProps).view },
    });
  } catch (err) {
    mount.textContent = 'Something went wrong rendering this page.';
    await blogLogger.captureException(err, {
      tags: { component: 'blog-app' },
      extra: { view: props.view },
    });
  }
}

void boot();
