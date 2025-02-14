<?php
require 'db.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}
if (!isset($_GET['lesson_id'])) {
    header("Location: dashboard_student.php");
    exit();
}

$lesson_id = $_GET['lesson_id'];
$lesson_stmt = $pdo->prepare("
    SELECT l.title, a.description 
    FROM lessons l
    LEFT JOIN assignments a ON l.id = a.lesson_id
    WHERE l.id = ? AND l.student_id = ?
");
$lesson_stmt->execute([$lesson_id, $_SESSION['user_id']]);
$lesson = $lesson_stmt->fetch();

if (!$lesson) {
    header("Location: dashboard_student.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_answer'])) {
    $answer_text = $_POST['answer_text'];
    $file_path = NULL;
    if (!empty($_FILES['file']['name'])) {
        $upload_dir = "uploads/";
        $file_name = basename($_FILES["file"]["name"]);
        $file_path = $upload_dir . $file_name;
        move_uploaded_file($_FILES["file"]["tmp_name"], $file_path);
    }
    $stmt = $pdo->prepare("INSERT INTO student_answers (lesson_id, student_id, answer_text, file_path) VALUES (?, ?, ?, ?)");
    $stmt->execute([$lesson_id, $_SESSION['user_id'], $answer_text, $file_path]);

    header("Location: submit_answer.php?lesson_id=$lesson_id");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_answer'])) {
    $stmt = $pdo->prepare("DELETE FROM student_answers WHERE id = ? AND student_id = ?");
    $stmt->execute([$_POST['delete_answer'], $_SESSION['user_id']]);
    header("Location: submit_answer.php?lesson_id=$lesson_id");
    exit();
}
$answers_stmt = $pdo->prepare("
    SELECT id, answer_text, file_path, submitted_at 
    FROM student_answers 
    WHERE lesson_id = ? AND student_id = ?
    ORDER BY submitted_at DESC
");
$answers_stmt->execute([$lesson_id, $_SESSION['user_id']]);
$answers = $answers_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сдать задание</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 50%;
        }
        h2 {
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        input, textarea, button {
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            width: 80%;
        }
        button {
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
            width: 85%;
        }
        button:hover {
            background-color: #0056b3;
        }
        .back {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            background-color: #FF5733;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
        }
        .back:hover {
            background-color: #C70039;
        }
        .answer {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
            text-align: left;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 5px;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Сдать задание</h2>
        <h3><?= htmlspecialchars($lesson['title']) ?></h3>
        <p><strong>Задание:</strong> <?= htmlspecialchars($lesson['description']) ?></p>

        <form method="post" enctype="multipart/form-data">
            <label>Ваш ответ:</label>
            <textarea name="answer_text" required></textarea>
            <input type="file" name="file">
            <button type="submit" name="submit_answer">Отправить</button>
        </form>

        <h2>Ваши ответы</h2>
        <?php if (!empty($answers)): ?>
            <?php foreach ($answers as $answer): ?>
                <div class="answer">
                    <p><strong>Ответ:</strong> <?= htmlspecialchars($answer['answer_text']) ?></p>
                    <?php if ($answer['file_path']): ?>
                        <p><a href="<?= htmlspecialchars($answer['file_path']) ?>" download>Скачать файл</a></p>
                    <?php endif; ?>
                    <p><small>Отправлено: <?= $answer['submitted_at'] ?></small></p>
                    <form method="post">
                        <button type="submit" name="delete_answer" value="<?= $answer['id'] ?>" class="delete-btn">Удалить</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Вы ещё не отправили ответ.</p>
        <?php endif; ?>

        <a class="back" href="dashboard_student.php">Вернуться назад</a>
    </div>
</body>
</html>