 <html>
	<head>
		<script type="text/javascript">
			function getHTTPObject() { if (typeof XMLHttpRequest != 'undefined') { return new XMLHttpRequest(); } try { return new ActiveXObject("Msxml2.XMLHTTP"); } catch (e) { try { return new ActiveXObject("Microsoft.XMLHTTP"); } catch (e) {} } return false; } 
		</script>
	</head>
	<body>
<?php if (isset($_SESSION['USER'])): ?>
		<a href="">My Account</a>
		<a href="?LOGOUT">Log out</a>
<?php else: ?>
		<form action="?LOGIN" method="post">
			Username: <input type="text" name="USERNAME" value="">
			Password: <input type="password" name="PASSWORD" value="">
			<input type="submit" value="Log in">
		</form>
<?php endif; ?>
	</body>
</html>
