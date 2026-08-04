<!DOCTYPE html>
<html lang="$ContentLocale" class="talaria-theme">
<head>
	<% base_tag %>
	<title><% if $MetaTitle %>$MetaTitle<% else %>$Title<% end_if %> &raquo; $SiteConfig.Title</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	$MetaTags(false)
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="/_resources/themes/talaria/dist/css/main.css">
	<link rel="icon" type="image/svg+xml" href="/_resources/themes/talaria/images/favicon.svg">
</head>
<body class="$ClassName.ShortName<% if not $Menu(2) %> no-sidebar<% end_if %>" <% if $i18nScriptDirection %>dir="$i18nScriptDirection"<% end_if %>>
	<% include Header %>
	<main class="site-main" role="main">
		<div class="site-main__inner<% if $Menu(2) %> has-sidebar<% end_if %>">
			$Layout
		</div>
	</main>
	<% include Footer %>
</body>
</html>
