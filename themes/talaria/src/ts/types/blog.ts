export type BlogTag = string;

export interface BlogPostListItem {
  slug: string;
  title: string;
  excerpt: string;
  author: string;
  publishedAt: string;
  tags: BlogTag[];
}

export interface BlogPost extends BlogPostListItem {
  body: string;
}

export interface BlogListProps {
  view: 'list';
  entity: string;
  listUrl: string;
  detailBaseUrl: string;
  posts: BlogPostListItem[];
}

export interface BlogDetailProps {
  view: 'detail';
  entity: string;
  listUrl: string;
  detailBaseUrl: string;
  slug: string | null;
  post: BlogPost | null;
  found: boolean;
}

export type BlogProps = BlogListProps | BlogDetailProps;

export function isBlogListProps(props: BlogProps): props is BlogListProps {
  return props.view === 'list';
}

export function isBlogDetailProps(props: BlogProps): props is BlogDetailProps {
  return props.view === 'detail';
}
