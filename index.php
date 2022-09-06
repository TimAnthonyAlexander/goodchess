<?php
namespace RealChess;

ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 180);
ini_set('memory_limit', '512M');


require(__DIR__.'/vendor/autoload.php');

session_start();

$cache = new Cache();
$cache->load();

$engine = false;

if (isset($_POST['sentdata'])) {
    $engine = $_SESSION['engine'] = $_POST['engine'] ?? false;
    $timepermove = $_SESSION['timepermove'] = $_POST['timepermove'] ?? 60;
} else {
    $engine = $_SESSION['engine'] ?? false;
    $timepermove = $_SESSION['timepermove'] ?? 60;
}


$resetcache = $_POST['engine'] ?? false;

if ($resetcache) {
    $cache->delete();
}
?>

<div class="settings">
    <form action="" id="settingsform" method="post">
        <input type="hidden" name="sentdata" value="1">
        <label for="engine">
            <input type="checkbox" value="1" <?= $engine ? 'checked' : '' ?> name="engine" id="engine" onchange="this.form.submit()"> Engine
        </label><br>
        <label for="resetcache">
            <input type="checkbox" value="1" name="resetcache" id="resetcache" onchange="this.form.submit()"> Reset Cache
        </label><br>
        <label for="timepermove">
            Time per move: <input type="number" value="<?=$timepermove?>" name="timepermove" id="timepermove" onchange="this.form.submit()"> seconds<br>
        </label>
        <input type="submit" value="Save">
    </form>
</div>

<div class="board">
<?php
require(__DIR__."/chessboard.php");
?>
</div>


<style>
    .board{
        position: absolute;
        top: 5%;
        left: 50%;
        transform: translate(-50%, 0%);
        border: 4px dotted rebeccapurple;
    }
    .settings{
        position: absolute;
        top: 0;
        left: 0;
        width: 400px;
        height: 100vh;
        border: 4px dotted rebeccapurple;
    }
</style>

<?php
$cache->save();
