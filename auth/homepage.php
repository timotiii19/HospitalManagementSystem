<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HMSHomepage</title>
    <style>
        
        *{
          margin: 0;
          padding: 0;
          box-sizing: border-box;}

        body, html {
            height: 100%;
            font-family: 'Segoe UI', sans-serif;
        }

        .background {
            background: url('../images/banner5.png') no-repeat center center fixed; 
            background-size: Full;
            background-position: center;
            height: 100vh;
            width: 100%;
            position: relative;
        }

        .tagline{
            font-family: 'Roboto', sans-serif;

        }

        .headline{
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;

        }

        .subtext{
            font-family: 'arial', sans-serif;
            line-height: 1.5;

        }


        .top-bar {
            position: absolute;
            top: 50px;
            right: 50px;
        }

        .login-button {
            width: 150px;
            height: 65px;
            margin-right: 10px;
            padding: 10px 20px;
            background-color: #ffffffcc;
            color: #333;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .login-button:hover {
            background-color: #ff6b97;
            color: white;
        }
        .homepage-text {
            position: absolute;
            top: 44.5%;
            left: 19.2%;
            transform: translate(-10%, -45%);
            color: white;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7);
            max-width: 800px;
        }
        .homepage-text h1 {
            font-size: 50px;
            margin-bottom: 10px;
            margin-top: 10px;
        }

        .homepage-text p {
            font-size: 14px;
            margin-right: 140px;
            
        }

        .homepage-text at {
            font-size: 24px;
            margin-right: 60px;
        }
        .logo-img {
            width: 700px; /* adjust as needed */
            margin-bottom: 60px;
            display: block;
            margin-left: -90px;
            top: -50px;
            position: relative;
        }




    </style>
</head>
<body>
    <div class="background">
        <div class="top-bar">
            <a href="role_selection.php">
                <button class="login-button">Login</button>
            </a>
        </div>
        <div class="homepage-text">
            <img src="../images/hosplogo.png" alt="Hospital Logo" class="logo-img">
            <at class="tagline">Compassion in Care, Excellence in Healing</at>
            <h1 class="headline">Leading the way in medical excellence </h1>
            <p class="subtext">CHART Memorial Hospital has been recognized as one of the Top Healthcare Institutions, known for its advanced integration of cutting-edge technology, streamlined processes, and state-of-the-art medical systems. Our commitment to operational excellence and data-driven decision-making ensures the highest quality of patient care, supported by advanced analytics and intelligent healthcare solutions.</p>
                    <p class="subtext" style="margin-top: 20px; font-style: italic;">
            <strong>CHART</strong> represents the names of the developers behind this system:<br>
            <strong>C</strong>harles Vizcarra, <strong>H</strong>annah Valenzuela, <strong>A</strong>rabella Valerio, <strong>R</strong>oeven Peji, and <strong>T</strong>imothy Talagtag.
        </p>        
        </div>
    </div>
</body>
</html>