<?php

ini_set('session.gc_maxlifetime', 0);

$cookie_params = session_get_cookie_params();
session_set_cookie_params(
    0,
    $cookie_params['path'],
    $cookie_params['domain'],
    $cookie_params['secure'],
    $cookie_params['httponly']
);

session_start();
include('others/sanitizer.php');
include('others/connection.php');

$errorColor = "";
$errorText = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["submit1"])) {
        // Sanitize input data
        $surname = data($_POST['surnameSelect']);
        $email = data($_POST['email']);
        $firstname = data($_POST['firstname']);
        $lastname = data(ucwords(strtolower($_POST['lastname'])));
        $age = data($_POST['age']);

        // Check if surname is selected
        if (empty($surname)) {
            $errorColor = "orangered";
            $errorText = "Please choose your last name from the options provided below.";
        } else {
            // Check if surname matches last name
            if ($surname != $lastname) {
                $errorColor = "orangered";
                $errorText = "Your surname and last name in your details do not match.";
            } else {
                // Check if person exists in the database
                $sql_check = "SELECT * FROM listofinvited WHERE firstname = ? AND lastname = ?";
                $stmt_check = mysqli_prepare($conn, $sql_check);
                mysqli_stmt_bind_param($stmt_check, 'ss', $firstname, $lastname);
                mysqli_stmt_execute($stmt_check);
                $result_check = mysqli_stmt_get_result($stmt_check);

                if (mysqli_num_rows($result_check) > 0) {
                    $errorColor = "orangered";
                    $errorText = "The details you have submitted already exist in our database.";
                } else {
                    // Check if the surname is valid and within the allowed invitations
                    $sql_check2 = "SELECT * FROM validsurnames WHERE surname = ?";
                    $stmt_check2 = mysqli_prepare($conn, $sql_check2);
                    mysqli_stmt_bind_param($stmt_check2, 's', $lastname);
                    mysqli_stmt_execute($stmt_check2);
                    $result_check2 = mysqli_stmt_get_result($stmt_check2);

                    if (mysqli_num_rows($result_check2) === 1) {
                        $row_check2 = mysqli_fetch_assoc($result_check2);
                        $numberofinvited = $row_check2['numberofinvited'];

                        // Count current invitations for the surname
                        $sql_count = "SELECT COUNT(*) AS count FROM listofinvited WHERE lastname = ?";
                        $stmt_count = mysqli_prepare($conn, $sql_count);
                        mysqli_stmt_bind_param($stmt_count, 's', $lastname);
                        mysqli_stmt_execute($stmt_count);
                        $result_count = mysqli_stmt_get_result($stmt_count);

                        if ($result_count) {
                            $row_count = mysqli_fetch_assoc($result_count);
                            $count = $row_count['count'];

                            if ($count < $numberofinvited) {
                                // Validate other fields
                                if (empty($firstname)) {
                                    $errorColor = "orangered";
                                    $errorText = "First name should not be empty.";
                                } else if (empty($email)) {
                                    $errorColor = "orangered";
                                    $errorText = "Email should not be empty. We'll send the soft copy of the invitation ticket to your email.";
                                } else if (empty($age)) {
                                    $errorColor = "orangered";
                                    $errorText = "Age should not be empty.";
                                } else {
                                    // Validate age
                                    if ($age < 18) {
                                        $errorColor = "orangered";
                                        $errorText = "You must be of legal age!";
                                    } else {
                                        $random_numbers = array();
                                        // Generate 6 random 6-digit numbers
                                        for ($i = 0; $i < 3; $i++) {
                                            $random_numbers = rand(0, 999999);
                                            // $random_numbers = "99999";
                                            if ($random_numbers < 100000) {
                                                $addOne = rand(100000, 200000);
                                                $random_numbers += $addOne;
                                            }
                                        }
                                        $firstname = ucwords(strtolower($firstname));
                                        $lastname = ucwords(strtolower($lastname));
                                        // Insert into database
                                        $sql_insert = "INSERT INTO listofinvited (email, firstname, lastname, age, onetimenumber) VALUES (?, ?, ?, ?, ?)";
                                        $stmt_insert = mysqli_prepare($conn, $sql_insert);
                                        mysqli_stmt_bind_param($stmt_insert, 'sssii', $email, $firstname, $lastname, $age, $random_numbers);

                                        if (mysqli_stmt_execute($stmt_insert)) {
                                            // delete all data of old sessions before adding new data ;>
                                            unset($_SESSION['firstname'], $_SESSION['lastname'], $_SESSION['email'], $_SESSION['age'], $_SESSION['onetimenumber']);
                                            // Transferring the post data form to gentest to generate tickets of invited ppls
                                            $_SESSION['firstname'] = $firstname;
                                            $_SESSION['lastname'] = $lastname;
                                            $_SESSION['email'] = $email;
                                            $_SESSION['age'] = $age;
                                            $_SESSION['onetimenumber'] = $random_numbers;
                                            $errorColor = "green";
                                            $errorText = "Generating a ticket and sending it to you via email!";
                                            echo "<script>
                                                setTimeout(function() {
                                                    window.location.href = 'https://invitation.free.nf/others/generate.php'
                                                }, 2000);
                                            </script>";
                                        } else {
                                            $errorColor = "orangered";
                                            $errorText = "Error submitting to the database: " . mysqli_error($conn);
                                        }
                                    }
                                }
                            } else {
                                $lastnamegood = ucwords(strtolower($lastname));
                                $errorColor = "orangered";
                                $errorText = "I apologize, but {$lastnamegood} is already at full capacity, with all {$numberofinvited} spot(s) taken.";
                            }
                        } else {
                            $errorColor = "orangered";
                            $errorText = "Error counting invitations: " . mysqli_error($conn);
                        }
                    } else {
                        $errorColor = "orangered";
                        $errorText = "Sorry, your surname is currently not in our database.";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<style>
html {
    background-color: #1a161c;
    color: black;
    font-family: poppins;
    overflow-x: hidden;
    font-size:17px;
}

#flexcontainer87184 {
    display: none;
}

body {
    text-align: center;
    margin: 0;
    padding: 0;
}

header {
    user-select: none;
    border-bottom: 1px solid rgba(0, 0, 0, 0.2);
    position: sticky;
    top: 0;
    background-color: rgba(91, 60, 136);
    z-index: 1000;
}

.flexcontainer1 {
    gap: 1rem;
    display: flex;
    justify-content: center;
    text-align: center;
}
.flexcontent1 {
    display: flex;
    justify-content: left;
}
.flexcontent2 {
    display: flex;
    flex: 5;
    gap: 1rem;
    justify-content: right;
}
.flexcontent2sub2 {
    margin-right: 1.5rem;
}

a {
    all: unset;
}

.form1 {
    cursor: pointer;
    color: white;
    transition: 0.2s;
}

.form1:hover {
    color: rgba(221, 221, 221, 0.4);
    transition: 0.2s;
}

.maincontent {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
}

.sidepanelcontentleft {
    flex: 3.5;
    min-width: 300px;
    border: 1px solid rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(0, 0, 0, 0.2);
}

.sidepanelcontentright {
    display: flex;
    flex: 1.6;
    height: 90vh;
    justify-content: center;
}

.contentleft {
    margin: 2rem;
}

.contentright {
    margin: 2rem;
}

.contain-main-form13{
    
}

.contain-main-form13-part1{
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
}.contain-main-form13-part1-sub0{
    flex: 1 0 99.6%;
    padding-bottom: 5px;
    border: 1px solid rgba(0, 0, 0, 0.2);
}.contain-main-form13-part1-sub1{
    flex: 1;
    padding-bottom: 5px;
    border: 1px solid rgba(0, 0, 0, 0.2);
}.contain-main-form13-part1-sub2{
    padding-bottom: 5px;
    border: 1px solid rgba(0, 0, 0, 0.2);
    flex: 11;
}

.contain-main-form13-part2{
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
}.contain-main-form13-part2-sub1{
    flex: 1;
    padding-bottom: 5px;
    border: 1px solid rgba(0, 0, 0, 0.2);
}.contain-main-form13-part2-sub2{
    flex: 1;
    border: 1px solid rgba(0, 0, 0, 0.2);
}

.controlcss{
    all:unset;
    font-family: 'Poppins';
    padding: 10px 3px 3px 10px;
    font-size:18px;
}
.controlcssbtn{
    all:unset;
    font-family: 'Poppins';
    padding: 10px 6px 6px 10px;
    font-size:20px;
    cursor: pointer;
}

.select-container {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    margin-bottom: 3rem;
}

.select-container select {
    all: unset;
    padding: 6px 2rem 7px 2rem;
    font-size: 18px;
    border-radius: 5px;
    background-color: transparent;
    color: white;
    cursor: pointer;
    outline: none;
}

.select-container-sub1{
    border: 1px solid rgba(0, 0, 0, 0.2);
    flex: 1 1;
    
}.select-container-sub2{
    border: 1px solid rgba(0, 0, 0, 0.2);
    flex: 2 2;
}
#surnameSelect{
    all: unset;
    color:black;
    padding: 7px;
    font-family: 'Poppins';
}
.totalrsvp{
    border: 1px solid rgba(0, 0, 0, 0.2);
    padding: 10px 2rem 10px 2rem;
    border-radius: 0.5rem;
    user-select: none; 
}

/* necessary for btn style */
.btnlogin {
    all: unset;
    font-family: 'Poppins';
    padding: 0.7rem;
    border: 1px solid rgba(207, 204, 204);
    border-radius: 0.5rem;
    width: 7rem;
    height: 1.4rem;
    cursor: pointer;
    position: relative;
    background-color: rgba(255, 255, 255, 0.0);
    overflow: hidden;
    transition: transform 0.8s ease-in-out;
}.btnlogin > :nth-child(1) {
    background-color: rgba(91, 60, 136);
    width: 10px;
    height: 10px;
    padding: 10px;
    position: absolute;
    border-radius: 100rem;
    top: 50%;
    left: 50%;
    transform: translate(-312%, 60%);
    transition: transform 0.8s ease-in;
}.btnlogin > :nth-child(2) {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    transition: 0.9s ease-in;
}.btnlogin > :nth-child(3) {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, 100%);
    color: white;
    transition: transform 0.9s ease-out;
}.btnlogin:hover > :nth-child(1) {
    transform: translate(-50%, -50%) scale(10);
}.btnlogin:hover > :nth-child(2){
    opacity: 0;
    transform: translate(-50%, -188%);
}.btnlogin:hover > :nth-child(3){
    transform: translate(-50%, -50%);
} 


@media (min-width: 701px) {
    .select-container-sub1{
        /* select input ito */
        /* padding-bottom: 3px !important; */
        flex-grow: 1 !important;
        flex-shrink: 1 !important;
    }
    .contain-main-form13-part2-sub1{
        flex-basis: 200px !important;
        flex-grow: 1 !important;
        flex-shrink: 1 !important;
    }
    .contain-main-form13-part2-sub2{
        flex-basis: 200px !important;
        flex-grow: 5 !important;
        flex-shrink: 1 !important;
    }
    #surnameSelect{
    }
    .controlcssbtn{
        white-space: nowrap;
    }
    html{
        background-image: url(images/landscapebg.png);
        width: 100%;
        height: 100vh;
        background-repeat: no-repeat;
        background-position: center;
        background-size: cover;
        background-attachment: fixed;
        color: black;
    }
    body{
        color: black;
    }
    .controlcss {
        width: fit-content 100vh;
    }
}

main{
    background-color: rgba(221, 221, 221, 0.2);
    backdrop-filter: blur(0.25rem);
}

@media (max-width: 700px) {
    html{
        background-image: url(images/portraitbg.png);
        width: 100%;
        height: 100vh;
        background-repeat: no-repeat;
        background-position: center;
        background-size: cover;
        background-attachment: fixed;
        color: black;
    }

    main{
        background-color: rgba(221, 221, 221, 0.2);
        backdrop-filter: blur(0.25rem);
    }

    #flexcontainer1 {
        display: none;
    }

    #flexcontainer87184 {
        color: white;
        display: flex;
        flex-direction: row;
    }
    .flexcontainer87184content1 {
        flex: 0;
        display: flex;
        justify-content: left;
    }
    .flexcontainer87184content2 {
        flex: 1;
        display: flex;
        justify-content: right;
        position: relative;
    }
    .sdvocovnxxzer246dg {
        position: relative;
        margin-right: 2rem;
    }
    .flexcontainer87184content2sub1 {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
    .menu {
        border: 1px solid rgba(221, 221, 221, 0.8);
        padding: 5px 10px 5px 10px;
        border-radius: 10000vh;
        cursor: pointer;
        user-select: none;
    }
    .welcome {
        margin-left: 1.5rem;
        cursor: pointer;
        user-select: none;
    }
    .controller53532 {
        cursor: pointer;
        user-select: none;
        transition: 0.6s;
        padding: 5px;
    }
    .controller53532:hover {
        color: rgba(221, 221, 203);
        box-shadow: inset 0 0 1rem 0.3px rgba(221, 221, 221, 0.6);
        transition: 0.4s;
        border-radius: 5px;
    }
    .flexcontainer87184content2sub1sub1 {
        z-index: 99;
        background-color: rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(2px);
        padding: 1rem;
        position: absolute;
        top: 80%;
        left: 50%;
        transform: translate(-98%, 0%);
    }
    .controlcss{
        width: auto;

    }
}

@media (min-width: 490px) and (max-width: 621px) {
    .controlcss{
        width: 10rem;
        padding: 18px 5px 10px 5px;
        height: auto;
        font-size: 16px;
    }
    #surnameSelect{
        padding: 18px 5px 15px 5px;
        font-size: 16px;
    }
    .controlcssbtn{
        padding: 18px 5px 15px 5px;
        font-size: 16px;
    }
}

@media (min-width: 408px) and (max-width: 434px) {
    .controlcss{
        padding: 18px 5px 10px 5px;
        height: auto;
        font-size: 16px;
    }
    #surnameSelect{
        padding: 18px 5px 15px 5px;
        font-size: 16px;
    }
    .controlcssbtn{
        padding: 18px 5px 15px 5px;
        font-size: 16px;
    }
}

@media (max-width: 407px) {
    .controlcss{
        padding: 15px 5px 12px 5px;
        height: auto;
        font-size: 15px;
    }
    #surnameSelect{
        padding: 15px 5px 12px 5px;
        font-size: 15px;
    }
    .controlcssbtn{
        padding: 15px 5px 12px 5px;
        font-size: 15px;
    }
}
@media (min-width: 349px) and (max-width: 374px){
    .controlcss{
        padding: 15px 3px 12px 3px;
        height: auto;
        font-size: 14px;
    }
    #surnameSelect{
        padding: 15px 3px 12px 3px;
        font-size: 14px;
    }
    .controlcssbtn{
        padding: 15px 3px 12px 3px;
        font-size: 14px;
    }
    .flexcontainer87184content2sub1sub1 {
        left: 44%;
    }
}

@media (max-width: 348px){
    .controlcss{
        padding: 15px 1px 12px 1px;
        height: auto;
        font-size: 12px;
    }
    #surnameSelect{
        padding: 15px 1px 12px 1px;
        font-size: 12px;
    }
    .controlcssbtn{
        padding: 15px 1px 12px 1px;
        font-size: 12px;
    }
    .flexcontainer87184content2sub1sub1 {
        left: 44%;
    }
}
</style>
<head>
    <link href="https://fonts.googleapis.com/css2?family=Poppins" rel="stylesheet">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>You're INVITED!</title>
</head>
<body>
    <header>
        <div id="flexcontainer1" class="flexcontainer1">
            <div class="flexcontent1">
                <h3 style="cursor: pointer;color:white;margin-left: 1.5rem;">Invitation!</h3>
            </div>
            <div class="flexcontent2">
                <div class="flexcontent2sub1">
                    <a href="/whoisinvited">
                        <h3 class="form1">Who's invited?</h3>
                    </a>
                </div>
                <div class="flexcontent2sub2">
                    <h3 class="form1">Designated area</h3>
                </div>
            </div>
        </div>
        <div id="flexcontainer87184">
            <div class="flexcontainer87184content1">
                <a href="." class="welcome">
                    <h3>Invitation</h3>
                </a>
            </div>
            <div class="flexcontainer87184content2">
                <div class="sdvocovnxxzer246dg">
                    <div class="flexcontainer87184content2sub1">
                        <p class="menu" id="menu" onclick="openMenu()">☰</p>
                        <div style="display:none;" id="menucontents" class="flexcontainer87184content2sub1sub1">
                            <a href="/whoisinvited">
                                <p class="controller53532">Who's invited?</p>
                            </a>
                            <a href=".">
                                <p class="controller53532">Designated area</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <main>
        <div class="maincontent">
            <div class="sidepanelcontentleft">
                <div class="contentleft">
                    <div class="form-13-412">
                        <p style="color: <?php echo $errorColor; ?>;"><?php echo $errorText; ?></p>
                        <form action="<?php echo htmlspecialchars('./'); ?>" method="post">
                            <div class="contain-main-form13">
                                <div class="contain-main-form13-part0">
                                    <div class="select-container">
                                        <div class="select-container-sub1">
                                            <input class="controlcss" type="text" id="surnameInput" oninput="filterOptions()" name="selectedSurname" placeholder="Type surname to filter list...">
                                        </div>
                                        <div class="select-container-sub2">
                                            <select class="controlcss" id="surnameSelect" name="surnameSelect"></select>
                                        </div>
                                    </div>
                                </div>
                                <div class="contain-main-form13-part1">
                                    <div class="contain-main-form13-part1-sub0">
                                        <input class="controlcss" type="email" name="email" placeholder="Email" required>
                                    </div>
                                    <div class="contain-main-form13-part1-sub1">
                                        <input class="controlcss" type="text" name="firstname" placeholder="First Name" required>
                                    </div>
                                    <div class="contain-main-form13-part1-sub2"><input class="controlcss" type="text" name="lastname" placeholder="Last Name" required></div>
                                </div>
                                <div class="contain-main-form13-part2">
                                    <div class="contain-main-form13-part2-sub1">
                                        <input class="controlcss edit123" type="number" id="age" name="age" min="0" placeholder="Age" required>
                                    </div>
                                    <div class="contain-main-form13-part2-sub2">
                                        <button class="controlcssbtn" type="submit" name="submit1">Generate Ticket</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="sidepanelcontentright">
                <div class="contentright">
                    <?php
                        $sql = "select COUNT(lastname) as counts from listofinvited";
                        $stmt1 = mysqli_prepare($conn, $sql);
                        mysqli_stmt_execute($stmt1);
                        $stmt3 = mysqli_stmt_get_result($stmt1);

                        if (mysqli_num_rows($stmt3) === 1) {
                            $row = mysqli_fetch_assoc($stmt3);
                            $counts = $row['counts'];

                            if ($counts >= 1) {
                                echo "
                                    <div class='totalrsvp'>
                                        <h4 style='font-size: 20px;'>Total RSVP</h4>
                                        <div style='margin-top: -20px;'><h5 style='font-size: 18px;'>". $counts ."</h5></div>
                                    </div>
                                ";
                            }else {
                                echo "
                                    <div class='totalrsvp'>
                                        <h4 style='font-size: 20px;'>Total RSVP</h4>
                                        <div style='margin-top: -32px;'><h6 style='font-size: 18px;'>No records found.</h6></div>
                                    </div>
                                ";
                            }
                            
                        }else {
                            echo "
                                <div class='totalrsvp'>
                                    <h4 style='font-size: 20px;'>Total RSVP</h4>
                                    <div style='margin-top: -32px;'><h6 style='font-size: 18px;'>No records found.</h6></div>
                                </div>
                            ";
                        }
                    ?>
                    <div style="margin-top:1.2rem;text-align:-webkit-center;">
                        <button class="btnlogin" onclick="login()">
                            <span class="jwcolor"></span>
                            <span>Admin</span>
                            <span>Login</span></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
<script>
    function login(){
        setTimeout(function(){
            window.location.href = 'whoisinvited'
        },1700);
    }
    function openMenu() {
        var menu = document.getElementById('menu');
        var menucontents = document.getElementById('menucontents');
        if (menu.textContent == "☰") {
            menu.textContent = "✕";
            menucontents.style.display = "block";
        } else {
            menu.textContent = "☰";
            menucontents.style.display = "none";
        }
    }
</script>

<script>
    const surnames = [
        { name: "Aradaza", value: "Aradaza" },
        { name: "Bandahala", value: "Bandahala" },
        { name: "Bayeta", value: "Bayeta" },
        { name: "Bragais", value: "Bragais" },
        { name: "Corpuz", value: "Corpuz" },
        { name: "Dadivas", value: "Dadivas" },
        { name: "Dinzon", value: "Dinzon" },
        { name: "Gomez", value: "Gomez" },
        { name: "Honorio", value: "Honorio" },
        { name: "Ison", value: "Ison" },
        { name: "Malinis", value: "Malinis" },
        { name: "Maltu", value: "Maltu" },
        { name: "Menguito", value: "Menguito" },
        { name: "Mora", value: "Mora" },
        { name: "Nerval", value: "Nerval" },
        { name: "Pagulayan", value: "Pagulayan" },
        { name: "Palarpalar", value: "Palarpalar" },
        { name: "Pedreñia", value: "Pedreñia" },
        { name: "Peradilla", value: "Peradilla" },
        { name: "Rogel", value: "Rogel" },
        { name: "Saballero", value: "Saballero" },
        { name: "Sampaga", value: "Sampaga" },
        { name: "Sanchez", value: "Sanchez" },
        { name: "Santos", value: "Santos" },
        { name: "Tan", value: "Tan" },
        { name: "Toribio", value: "Toribio" },
        { name: "Villanueva", value: "Villanueva" }
    ];

    const surnameInput = document.getElementById('surnameInput');
    const surnameSelect = document.getElementById('surnameSelect');

    populateOptions();

    function populateOptions() {
        surnameSelect.innerHTML = '';

        const defaultOption = document.createElement('option');
        defaultOption.text = 'Select a surname';
        defaultOption.value = '';
        surnameSelect.add(defaultOption);

        surnames.forEach(surname => {
            const option = document.createElement('option');
            option.text = surname.name;
            option.value = surname.value;
            surnameSelect.add(option);
        });
    }

    function filterOptions() {
        const filterText = surnameInput.value.toLowerCase();
        
        surnameSelect.innerHTML = '';

        const defaultOption = document.createElement('option');
        defaultOption.text = 'Select a surname';
        defaultOption.value = '';
        surnameSelect.add(defaultOption);

        surnames.forEach(surname => {
            if (surname.name.toLowerCase().includes(filterText)) {
                const option = document.createElement('option');
                option.text = surname.name;
                option.value = surname.value;
                surnameSelect.add(option);
            }
        });
    }
</script>

</html>