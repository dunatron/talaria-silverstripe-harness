<% include SideBar %>
<div class="content-container talaria-harness">
	<article>
		<h1>$Title</h1>
		<div class="content">$Content</div>

		<% if $FlashMessage %>
			<p class="talaria-flash">$FlashMessage</p>
		<% end_if %>

		<% with $ConfigStatus %>
			<section class="talaria-status">
				<h2>Talaria config</h2>
				<% if $Configured %>
					<p class="ok">Configured — events should ingest.</p>
				<% else %>
					<p class="warn">Not configured — set <code>TALARIA_DSN</code> and <code>TALARIA_API_KEY</code> in <code>.env</code>, then <code>ddev exec vendor/bin/sake dev/build flush=1</code>.</p>
				<% end_if %>
				<ul>
					<li><strong>DSN</strong>: $Dsn</li>
					<li><strong>API key</strong>: $ApiKey</li>
					<li><strong>Environment</strong>: $Environment</li>
					<li><strong>Release</strong>: $Release</li>
					<li><strong>Queue size</strong>: $QueueSize</li>
				</ul>
			</section>
		<% end_with %>

		<section class="talaria-practice">
			<h2>Talaria Logger showcase</h2>
			<p>This demo wraps the Injector <code>TalariaClient</code> with <code>Talaria\Logger</code> (do not call <code>Talaria::init</code>). Filter dashboard events by <code>entity=BookstorePage</code> / <code>feature=bookstore</code>.</p>
			<ul>
				<li><code>logger</code> / <code>child</code> — feature + component scopes (<code>cart</code>, <code>inventory</code>, <code>payments</code>)</li>
				<li>Tags vs extra — low-cardinality <code>operation</code>/<code>step</code> tags; SKUs and cart dumps in extra</li>
				<li><code>withMinLevel('error')</code> — noisy inventory debug dropped on the stock-failure path</li>
				<li><code>isLevelEnabled('debug')</code> — expensive cart snapshot only when debug is enabled</li>
				<li><code>captureException</code> — payment / stock / validation failures</li>
				<li>PSR-3 placeholders — <code>Order {order_id}…</code> / <code>Stock reserved for {sku}</code></li>
			</ul>
			<p>Contrast with <a href="/checkout-demo">Checkout workflow demo</a>, which instruments the same style of flow via <code>TalariaClient</code> directly.</p>
		</section>

		<% with $CartSummary %>
			<section class="talaria-status">
				<h2>Cart</h2>
				<p>
					<strong>$ItemCount</strong> item(s) — subtotal <strong>$SubtotalFormatted</strong>
					&nbsp;·&nbsp; <a href="$CatalogLink">Catalog</a>
					&nbsp;·&nbsp; <a href="$CartLink">Cart</a>
					&nbsp;·&nbsp; <a href="$CheckoutLink">Checkout</a>
					<% if not $IsEmpty %>
						&nbsp;·&nbsp; <a href="$ClearLink">Clear cart</a>
					<% end_if %>
				</p>
			</section>
		<% end_with %>

		<% if $CurrentView = 'catalog' %>
			<section class="talaria-actions">
				<h2>Catalog</h2>
				<p>Add books to the session cart. Each add logs via a cart-scoped Logger child.</p>
				<ul class="talaria-grid">
					<% loop $CatalogBooks %>
						<li class="talaria-card kind-message">
							<strong>$Title</strong>
							<p>$Author · <code>$Sku</code> · $PriceFormatted</p>
							<p>
								<% if $InStock %>
									Stock: $Stock
									&nbsp;·&nbsp; <a class="talaria-btn" href="$AddLink">Add to cart</a>
								<% else %>
									<span class="warn">Out of stock</span>
								<% end_if %>
							</p>
						</li>
					<% end_loop %>
				</ul>
			</section>
		<% end_if %>

		<% if $CurrentView = 'cart' %>
			<section class="talaria-actions">
				<h2>Your cart</h2>
				<% if $CartSummary.IsEmpty %>
					<p>Cart is empty. <a href="$CartSummary.CatalogLink">Browse the catalog</a>.</p>
				<% else %>
					<ul class="talaria-grid">
						<% loop $CartLines %>
							<li class="talaria-card kind-message">
								<strong>$Book.Title</strong>
								<p>$Book.Author · <code>$Book.Sku</code> · $Book.PriceFormatted × $Qty = $LineTotalFormatted</p>
								<form method="post" action="$UpdateLink" style="display:inline">
									<input type="hidden" name="book" value="$Book.ID" />
									<label>Qty <input type="number" name="qty" value="$Qty" min="0" style="width:4rem" /></label>
									<button type="submit" class="talaria-btn">Update</button>
								</form>
								&nbsp; <a class="talaria-btn" href="$RemoveLink">Remove</a>
							</li>
						<% end_loop %>
					</ul>
					<p><a class="talaria-btn" href="$CartSummary.CheckoutLink">Proceed to checkout</a></p>
				<% end_if %>
			</section>
		<% end_if %>

		<% if $CurrentView = 'checkout' %>
			<section class="talaria-actions">
				<h2>Checkout outcomes</h2>
				<p>Simulate success and failure paths. Empty-cart and payment/stock buttons seed a sample line when the cart is empty (except Empty cart check, which clears first).</p>
				<% with $CartSummary %>
					<p>Current cart: <strong>$ItemCount</strong> item(s), <strong>$SubtotalFormatted</strong></p>
				<% end_with %>
				<ul class="talaria-grid">
					<% loop $CheckoutOutcomes %>
						<li class="talaria-card kind-$Kind">
							<a class="talaria-btn" href="$Href">$Title</a>
							<p>$Description</p>
						</li>
					<% end_loop %>
				</ul>
			</section>
		<% end_if %>
	</article>
</div>
