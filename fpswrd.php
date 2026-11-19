<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password | Biblio</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Alex+Brush&display=swap" rel="stylesheet">
  <style>
    :root {
      --sage-green: #b7c9a8;
      --blush-pink: #e8a7b0;
      --soft-white: #fdfdfb;
      --olive: #4a6b57;
      --text-gray: #555;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      background: url('bg1.jpeg') no-repeat center center fixed;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .back-arrow {
      position: absolute;
      top: 20px;
      left: 30px;
      font-size: 1.8rem;
      color: var(--sage-green);
      text-decoration: none;
      font-weight: bold;
      transition: color 0.3s ease, transform 0.2s ease;
    }

    .back-arrow:hover {
      color: var(--olive);
      transform: translateX(-5px);
    }

    .login-container {
      background-color: #ffffff;
      padding: 40px 35px;
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
      width: 100%;
      max-width: 400px;
    }

    .login-container h2 {
      font-family: 'Alex Brush', serif;
      color: var(--olive);
      font-size: 2.4rem;
      text-align: center;
      margin-bottom: 16px;
    }

    .login-container p {
      font-size: 0.9rem;
      color: var(--text-gray);
      text-align: center;
      margin-bottom: 25px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--olive);
      font-size: 0.95rem;
    }

    input {
      width: 100%;
      padding: 12px 14px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 1rem;
      transition: border-color 0.3s ease;
      background-color: #fff;
      color: var(--text-gray);
    }

    input:focus {
      border-color: var(--sage-green);
      outline: none;
    }

    button {
      width: 100%;
      background-color: var(--blush-pink);
      color: white;
      border: none;
      padding: 12px;
      font-size: 1rem;
      border-radius: 25px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      font-weight: 500;
    }

    button:hover {
      background-color: #d98a99;
    }

    .footer {
      text-align: center;
      margin-top: 20px;
      font-size: 0.9rem;
    }

    .footer a {
      color: var(--sage-green);
      text-decoration: none;
    }

    .footer a:hover {
      text-decoration: underline;
      color: var(--olive);
    }
  </style>
</head>
<body>
  <a href="login.php?skipSplash=true" class="back-arrow">←</a>
  <div class="login-container">
    <h2>Reset Password</h2>
    <p>Enter your email and we'll send you a link to reset it.</p>
    <form action="reset-sent.html" method="POST">
      <label for="email">Email Address</label>
      <input type="email" id="email" name="email" required>
      <button type="submit">Send Reset Link</button>
    </form>
    <div class="footer">
      Back to <a href="login.php">Login</a>
    </div>
  </div>
</body>
</html>