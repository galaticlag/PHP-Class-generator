<?php
require '_system/_config.inc.php';

$classDir = scandir(dirname(__FILE__) . '/_class', SCANDIR_SORT_ASCENDING);
foreach ($classDir as $id => $fichier) {
    if (preg_match('#.class.php$#', $fichier)) {
        require '_class/' . $fichier;
    }
}

$book = new books([
    'JOIN' => 'INNER',
    'id' => Database::setParam(1, '=', 'TABLE_author')
], false, true);
echo $book->display();
echo $book->get_FK_authorauthor()->get_name();

$author = new author(['id' => 1]);

$new_book = new books();
$new_book->set_name('php for dummies');
$new_book->set_author(1);
$new_book->save(true);

echo $new_book->get_FK_authorauthor()->get_name();

books::delete([
    'name' => 'php for dummies',
    'LIMIT' => 1
], true);