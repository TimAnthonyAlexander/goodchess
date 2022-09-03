<?php
namespace RealChess;

ini_set('display_errors', 1);
error_reporting(E_ALL);


require(__DIR__.'/vendor/autoload.php');

?>

<div class="settings">

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
