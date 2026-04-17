<?php
require_once __DIR__ . '/../models/PostModel.php';

class PostController {
    private $postModel;

    public function __construct() {
        $this->postModel = new PostModel();
    }

    public function index() {
        $posts = $this->postModel->getAll();
        include __DIR__ . '/../views/post/index.php';
    }

    public function detail() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            header('Location: index.php');
            exit;
        }

        $post = $this->postModel->getById($id);
        if (!$post) {
            header('Location: index.php');
            exit;
        }

        include __DIR__ . '/../views/post/detail.php';
    }

    public function edit($id = null) {
        $id = (int)($id ?? $_GET['id'] ?? 0);
        if (!$id) { header('Location: index.php?controller=post'); exit; }

        $post = $this->postModel->getById($id);
        if (!$post) { header('Location: index.php?controller=post'); exit; }

        include 'views/post/edit.php';
    }

    public function update($id = null) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { header('Location: index.php?controller=post'); exit; }

            $existing = $this->postModel->getById($id);
            $imageName = $existing['image'];

            if (!empty($_FILES['image']['name'])) {
                $imageName = time() . '_' . $_FILES['image']['name'];
                move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/posts/' . $imageName);
            }

            $data = [
                'id'      => $id,
                'title'   => trim($_POST['title']),
                'content' => $_POST['content'],
                'image'   => $imageName,
            ];

            $this->postModel->update($data);
            header("Location: index.php?controller=post&action=index");
            exit();
        }
    }
}
