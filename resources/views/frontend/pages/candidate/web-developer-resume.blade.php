<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume Template</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            color: #333;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .resume-container {
            width: 850px;
            border: 1px solid #ccc; /* Outer border for the resume */
            overflow: hidden;
            background-color: #fff;
        }

        .left-column,
        .right-column {
            padding: 10px;
            vertical-align: top;
            height: auto;
        }

        .left-column {
            background-color: #333;
            color: white;
            width: 30%;
            float: left;
            text-align: center;
            border-right: 1px solid #ccc;
            /* Border between left and right columns */
        }

        .left-column img {
            width: 150px;
            border-radius: 50%;
            margin-bottom: 20px;
            border: 2px solid white;
            /* Border around the profile image */
        }

        .right-column {
            background-color: #fff;
            width: 60%;
            float: left;
        }

        h1,
        h2,
        h3 {
            margin-bottom: 10px;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-size: 1.1em;
        }

        .info-block {
            margin-bottom: 20px;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            /* Border for each section */
        }

        .work-experience,
        .education,
        .skills,
        .languages {
            margin-bottom: 10px;
        }

        .timeline-item {
            margin-bottom: 10px;
        }

        .skills .skill-bar,
        .languages .language-bar {
            height: 5px;
            background-color: #333;
            margin-top: 5px;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>

<body>
    <div class="resume-container clearfix">
        <!-- Left Column -->
        <div class="left-column">


            <h1>Michael Scott</h1>
            <h3>General Manager</h3>

            <div class="info-block">
                <h3>About Me</h3>
                <p>Experienced General Manager with over 20 years in sales and management. Proven track record...</p>
            </div>

            <div class="info-block">
                <h3>Links</h3>
                <p>LinkedIn: <a href="#" style="color: white;">linkedin.com/in/michaelscott</a></p>
                <p>Twitter: <a href="#" style="color: white;">twitter.com/michaelscott</a></p>
            </div>

            <div class="info-block">
                <h3>References</h3>
                <p>Jim Halpert - Dunder Mifflin</p>
                <p>T: 123-456-7891</p>
                <p>E: jim.halpert@dundermifflin.com</p>
            </div>

            <div class="info-block">
                <h3>Hobbies</h3>
                <p>• Improv Comedy<br>• Basketball<br>• Magic Tricks</p>
            </div>
        </div>

        <!-- Right Column -->
        <div class="right-column">
            <div class="info-block work-experience">
                <h2 class="section-title">About Me</h2>
                <div class="timeline-item">

                    <p>Managed the Scranton branch, led a team, and implemented sales strategies...</p>
                </div>
            </div>
            <div class="info-block work-experience">
                <h2 class="section-title">Work Experience</h2>
                <div class="timeline-item">
                    <h3>Dunder Mifflin</h3>
                    <p>Scranton, Mar 2005 - May 2013</p>
                    <h4>Regional Manager</h4>
                    <p>Managed the Scranton branch, led a team, and implemented sales strategies...</p>
                </div>
            </div>

            <div class="info-block education">
                <h2 class="section-title">Education</h2>
                <div class="timeline-item">
                    <h3>University of Scranton</h3>
                    <p>Bachelor's Degree in Business Administration</p>
                    <p>Specialized in Sales and Marketing</p>
                </div>
            </div>

            <div class="info-block skills">
                <h2 class="section-title">Skills</h2>
                <p>Leadership</p>
                <div class="skill-bar" style="width: 90%;"></div>
                <p>Team Management</p>
                <div class="skill-bar" style="width: 80%;"></div>
            </div>

            <div class="info-block languages">
                <h2 class="section-title">Languages</h2>
                <p>English</p>
                <div class="language-bar" style="width: 95%;"></div>
                <p>French</p>
                <div class="language-bar" style="width: 70%;"></div>
            </div>
        </div>
    </div>

</body>

</html>
