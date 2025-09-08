<?php

namespace App\Controllers\API;
use App\Core\DB;

class AdminOps{
    protected $adminId;
    protected $pdo;

    public function __construct(){
        $this->adminId = $_SESSION['admin']['user_id'];
        $this->pdo =  DB::connection();
    }

    public function addBlogs(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $category = htmlspecialchars($_POST['category'] ?? '', ENT_QUOTES, 'UTF-8');
        $title = htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8');
        $author = htmlspecialchars($_POST['author'] ?? '', ENT_QUOTES, 'UTF-8');
        $allowedTags = '<p><br><b><strong><i><em><u><ol><ul><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre><a>';
        $content = strip_tags($_POST['content'] ?? '', $allowedTags);
        $image = null;

        $blogId = uniqid("blog_");

        $image = null;

        if (!$image) {
            $image = '';
        }

        if (empty($category) || empty($title) || empty($author) || empty($content) || empty($_FILES['image']['name'])){
            echo json_encode(['status' => 'error', 'message' => 'All field required']);
            return;
        }

        if ($_FILES['image']['name']) {
            $uploadDir = __DIR__ . "../../../../public/uploads/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename = time() . "_" . basename($_FILES['image']['name']);
            $targetFile = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                // store relative path (backend will serve it later)
                $image = $filename;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Image upload failed']);
                return;
            }
        }

        $stmt = $this->pdo->prepare("SELECT * FROM blogs WHERE title = ?");
        $stmt->execute([$title]);

        if ($stmt->rowCount() > 0){
            echo json_encode(['status' => 'error', 'message' => 'Blog already exist']);
            return;
        }

        $stmt = $this->pdo->prepare("INSERT INTO `blogs`(`blog_id`, `title`, `content`, `image`, `writer`, `category`) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$blogId, $title, $content, $image, $author, $category]);

        echo json_encode(['status' => 'success', 'message' => 'Blog added successfully']);
    }
}