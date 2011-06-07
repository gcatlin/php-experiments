<?php if (isset($_SESSION['USER'])): ?>
	<!--<a href="">My Account</a>-->
	<form method="post">
		<input type="hidden" name="LOGOUT" value="">
		<input type="submit" value="Log out">
	</form>
<?php else: ?>
	<form method="post">
		<input type="hidden" name="LOGIN" value="">
		<input type="hidden" name="CHALLENGE" value="">
		<input type="hidden" name="SALT" value="">
		Username: <input type="text" name="USERNAME" value="">
		Password: <input type="password" name="PASSWORD" value="">
		<input type="submit" value="Log in">
	</form>
<?php endif; ?>

<h2>Navigation</h2>
<ul>
	<li><a href="index.php">index.php</a></li>
	<li><a href="a.php">a.php</a></li>
	<li><a href="b.php">b.php</a></li>
	<li><a href="c.php">c.php</a></li>
</ul>

<?php dump('$_SESSION', $_SESSION); ?>
<?php dump('$_COOKIE', $_COOKIE); ?>

