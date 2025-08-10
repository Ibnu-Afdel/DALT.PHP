<?php

$id = $_GET['id'] ?? null;

view('demo.view.php', ['id' => $id]); 