<?php

include_once '_system/_config.inc.php';

define('INPUT_EXCEPTION', 'Input error : Please fill in all required fields (*).');
define('NO_EXCEPTION', 'All classes corresponding to your database have been successfully generated');

$message = '';

if (php_sapi_name() === 'cli' && !empty(dbhostname) && !empty(dbdatabase) && !empty(dbusername) && !empty(dbpassword) && !empty(dbtype)) {
	$generate = TRUE;
}
elseif (isset($_POST['submit-form'])) {

	define('dbhostname', $_POST['server']);
	define('dbdatabase', $_POST['base']);
	define('dbusername', $_POST['user']);
	define('dbpassword', $_POST['pass']);
	define('dbtype', $_POST['type']);

	$generate = TRUE;
}else { $generate = FALSE; }

if ($generate===TRUE) {
	if (dbhostname != '' && dbdatabase != '' && dbusername != '') {
			$structy_obj = new ClassGenerator();
			if ($structy_obj->getException() != '')
				$message = $structy_obj->getException();
	} else
		$message = INPUT_EXCEPTION;

	if ($message == '') {
		$message = NO_EXCEPTION;
	}
}

if (php_sapi_name() !== 'cli') {

?>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<meta name="description" content=''>
	<meta name="author" content=''>
	<title>PHP Classes Generator</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<link href="class-generator.css" rel="stylesheet">
</head>

<body onLoad="document.getElementById('server').focus();">
<div class="container">
		<form method="post" class="form-classGenerator">
			<h1 class="form-classGenerator-heading">PHP Classes Generation Tool</h1>
			<?php if (!empty($message)) { ?>
				<p class="form-classGenerator-message"><?php echo $message; ?></p>
			<?php } ?>
			<div class="form-classGenerator-input">
				<label for="dbType" class="sr-only">Database Type</label>
				<select class="form-control" placeholder="Database Type" name="type" id="type">
					<option value="mysql" <?php echo (dbtype == 'mysql')?'selected':''?> >MySQL</option>
					<option value="pgsql" <?php echo (dbtype == 'pgsql')?'selected':''?>>PostgreSQL</option>
				</select>
				<label for="inputHost" class="sr-only">Host</label>
				<input class="form-control" placeholder="Host*" type="text" id="server" name="server" value="<?php echo dbhostname; ?>">
				<label for="inputDatabase" class="sr-only">Database</label>
				<input class="form-control" placeholder="Database*" type="text" name="base" value="<?php echo dbdatabase; ?>">
				<label for="inputUserName" class="sr-only">User name</label>
				<input class="form-control" placeholder="User name*" type="text" name="user" value="<?php echo dbusername; ?>">
				<label for="inputPassword" class="sr-only">User password</label>
				<input class="form-control" placeholder="User password" type="password" name="pass">


				<button class="btn btn-lg btn-primary btn-block" type="submit" name="submit-form" value="1">Generate classes</button>
			</div>
		</form>
</div>
</body>

</html>
<?php
} else {
echo $message.PHP_EOL;
}