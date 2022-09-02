<?php
namespace RealChess;
ini_set('display_errors', 1);
error_reporting(E_ALL);


require(__DIR__.'/vendor/autoload.php');

$level = 2; // Default: Board::EVAL_LEVEL

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// check if board is initalized
if (!isset($_SESSION['board'])) {
    $_SESSION['board'] = new Board();
    $_SESSION['board']->initializeDefaultBoard();
}

$board = $_SESSION['board'];
assert($board instanceof Board);


if ($_POST['reset'] ?? '' === 'reset') {
    $board = new Board();
    $_SESSION['board'] = $board;
    $board->initializeDefaultBoard();
}

if ($_POST['move'] ?? false) {
    $rules = new Rules();
    $notation = Notation::generateFromString($_POST['move']);
    if ($rules->isValidFor($notation, $board)){
        $board->movePiece($notation);
        $_SESSION['board'] = $board;
    }
}

#$eval = TimFish::evaluateWhiteVsBlack($board);


$getBoard = $board->jsonSerialize();
$lastMove = $board->getLastMove();
if ((string) $lastMove !== (string) new Notation(Position::generateFromString('E2'), Position::generateFromString('E2'))){
    print ("<h3>Last move: " . $lastMove . "</h3>");
}

// Flip the board horizontally because the colors are reversed
foreach ($getBoard as $key => $value) {
    $getBoard[$key] = array_reverse($value, true);
}

foreach ($getBoard as $key => $column) {
    foreach ($column as $subkey => $piece) {
        if ($piece !== null){
            $row[$subkey][$key]['color'] = $piece['color'];
            $row[$subkey][$key]['name'] = $piece['piece'];
        } else {
            $row[$subkey][$key] = null;
        }
    }
}



function getImage(string $piece) {
    $pawnWhite = 'https://upload.wikimedia.org/wikipedia/commons/0/04/Chess_plt60.png';
    $pawnBlack = 'https://upload.wikimedia.org/wikipedia/commons/c/cd/Chess_pdt60.png';
    $rookWhite = 'https://upload.wikimedia.org/wikipedia/commons/5/5c/Chess_rlt60.png';
    $rookBlack = 'https://upload.wikimedia.org/wikipedia/commons/a/a0/Chess_rdt60.png';
    $knightWhite = 'https://upload.wikimedia.org/wikipedia/commons/2/28/Chess_nlt60.png';
    $knightBlack = 'https://upload.wikimedia.org/wikipedia/commons/f/f1/Chess_ndt60.png';
    $bishopWhite = 'https://upload.wikimedia.org/wikipedia/commons/9/9b/Chess_blt60.png';
    $bishopBlack = 'https://upload.wikimedia.org/wikipedia/commons/8/81/Chess_bdt60.png';
    $queenWhite = 'https://upload.wikimedia.org/wikipedia/commons/4/49/Chess_qlt60.png';
    $queenBlack = 'https://upload.wikimedia.org/wikipedia/commons/a/af/Chess_qdt60.png';
    $kingWhite = 'https://upload.wikimedia.org/wikipedia/commons/3/3b/Chess_klt60.png';
    $kingBlack = 'https://upload.wikimedia.org/wikipedia/commons/e/e3/Chess_kdt60.png';

    return match($piece) {
        'wP' => $pawnWhite,
        'bP' => $pawnBlack,
        'wR' => $rookWhite,
        'bR' => $rookBlack,
        'wN' => $knightWhite,
        'bN' => $knightBlack,
        'wB' => $bishopWhite,
        'bB' => $bishopBlack,
        'wQ' => $queenWhite,
        'bQ' => $queenBlack,
        'wK' => $kingWhite,
        'bK' => $kingBlack,
        default => '',
    };
}

// Print a html table with the pieces
echo '<table>';
$color = 'gray';

foreach ($row as $key => $column) {
    echo '<tr>';
    foreach ($column as $subkey => $piece) {
        $color = $color === 'gray' ? 'white' : 'gray';
        $position = new Position($subkey, $key);
        echo '<td id="'.$position.'" style="background-color: '.$color.'; width: 62px; height: 62px;" onclick="addToMove(\''.$position.'\')">';
        if ($piece !== null){
            echo '<img src="' . getImage(($piece['color'] ? 'w' : 'b') . $piece['name']) . '" alt="' . $piece['name'] . '">';
        }
        echo '</td>';
    }
    $color = $color === 'gray' ? 'white' : 'gray';
    echo '</tr>';
}
echo '</table>';

echo "<script>";
echo "function addToMove(notation){";
echo "var field = document.getElementById(notation);";
echo "field.style.backgroundColor = 'red';";
echo "var move = document.getElementById('move');";
echo "move.value = move.value + notation;";
echo "if (move.value.length === 4){";
// Submit
echo "document.getElementById('moveForm').submit();";
echo "}";
echo "}";
echo "</script>";

// Form to send the move
echo '<form action="" id="moveForm" method="post">';
echo '<input type="text" id="move" name="move" autofocus>';
echo '<input type="submit" value="Send">';
echo '</form>';

#TimFish::allPossibleMoves($board, false);

// Form to reset
echo '<form action="" method="post">';
echo '<input type="hidden" name="reset" value="reset">';
echo '<input type="submit" value="Reset">';
echo '</form>';
