<form action="" method="POST">
    <input type="tex" name="val"/>
    <button>Submit</button>
</form>
<?php
if ($_POST) {
    echo $_POST['val'];
}