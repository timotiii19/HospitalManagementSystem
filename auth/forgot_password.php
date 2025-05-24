<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', sans-serif;
      background: #f0f2f5;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    form {
      background: #fff;
      padding: 40px 30px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 420px;
    }

    h2 {
      text-align: center;
      margin-bottom: 25px;
      color: #333;
    }

    input, select, button {
      width: 100%;
      padding: 12px;
      margin-top: 15px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 15px;
    }

    input:focus, select:focus {
      border-color: #c94141;
      outline: none;
    }

    button {
      background: #c94141;
      color: white;
      border: none;
      font-weight: bold;
      transition: background 0.3s ease;
    }

    button:hover {
      background: #a32f2f;
    }

    .message {
      margin-top: 15px;
      font-size: 14px;
      text-align: center;
    }

    .message.success {
      color: green;
    }

    .message.error {
      color: red;
    }
  </style>
</head>
<body>

  <form method="POST">
    <h2>Forgot Password</h2>
    <input type="email" name="email" placeholder="Enter your email" required>
    <select name="role" required>
      <option value="">Select Role</option>
      <option value="Admin">Admin</option>
      <option value="Doctor">Doctor</option>
      <option value="Nurse">Nurse</option>
      <option value="Laboratory">Laboratory</option>
      <option value="Pharmacist">Pharmacist</option>
    </select>
    <button type="submit">Send Reset Link</button>
    
    <?php if (!empty($message)): ?>
      <div class="message <?php echo (strpos($message, 'sent') !== false) ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>
  </form>

</body>
</html>
