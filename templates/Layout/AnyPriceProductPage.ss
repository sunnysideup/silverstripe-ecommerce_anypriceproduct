<div id="Sidebar">
	<% include Sidebar_Cart %>
	<% include Sidebar_Products %>
</div>
<div id="Product">
	<h1 class="pageTitle">$Title</h1>
	<div class="productDetails">
		<div class="productImage">
<% if Image.ContentImage %>
			<img class="realImage" src="$Image.ContentImage.URL" alt="<% sprintf(_t("Product.IMAGE","%s image"),$Title) %>" />
<% else %>
			<img class="noImage" src="/ecommerce/images/productPlaceHolderThumbnail.gif" alt="<% _t("Product.NOIMAGEAVAILABLE","no image available") %>">
<% end_if %>
		</div>
		<div id="AddNewPriceForm">$AddNewPriceForm</div>
	</div>
	<% if Content %><div id="ContentHolder">$Content</div><% end_if %>
<% include OtherProductInfo %>
	<% if Form %><div id="FormHolder">$Form</div><% end_if %>
	<% if PageComments %><div id="PageCommentsHolder">$PageComments</div><% end_if %>
</div>




