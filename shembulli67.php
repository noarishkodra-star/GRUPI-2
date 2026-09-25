<form method="POST">
  <input type="text" name="emri">
  <button type="submit">Dërgo</button>
</form>

<?php
if (isset($_POST["emri"])) {
    echo $_POST["emri"];
}
?>