<?php
    $error = filter_input(INPUT_GET, 'err', $filter = FILTER_SANITIZE_STRING);

    if (! $error) {
        $error = 'Oops! An unknown error happened.';
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Custom WordPress Plugin Manager</title>
        <style type="text/css">
            *, *:before, *:after {
                -webkit-box-sizing: border-box;
                -moz-box-sizing: border-box;
                box-sizing: border-box;
            }
            body {
                font-family: "adobe-caslon-pro",Georgia,"Times New Roman",Times,serif;
                font-size: 14px;
                line-height: 1.428571429;
                color: #2d2c2c;
                background-color: #ebe9e6;
            }
            #error_table {
                width: 100%;
                height: 100%;
                display: table;
                position: absolute;
                z-index: 1;
            }
            #error_cell {
                display: table-cell;
                vertical-align: middle;
            }
            #container {
                width: 800px;
                margin-right: auto;
                margin-left: auto;                
            }
            #error_message {
                -webkit-font-smoothing: subpixel-antialiased;
                box-sizing: border-box;
                color: rgb(36, 127, 255);
                display: block;
                font-family: gt_walsheim_medium, 'Helvetica Neue', Helvetica, Arial, sans-serif;
                font-size: 92px;
                font-style: normal;
                font-weight: normal;
                height: 110px;
                letter-spacing: normal;
                line-height: 64px;
                margin-bottom: 0px;
                margin-left: 0px;
                margin-right: 0px;
                margin-top: 0px;
                padding-bottom: 32px;
            }
            #error_message SPAN {
                -webkit-font-smoothing: subpixel-antialiased;
                box-sizing: border-box;
                color: rgb(45, 44, 44);
                display: inline;
                font-family: gt_walsheim_medium, 'Helvetica Neue', Helvetica, Arial, sans-serif;
                font-size: 40px;
                font-style: normal;
                font-weight: normal;
                height: auto;
                letter-spacing: normal;
                line-height: 64px;
                width: auto;                
            }
            #error_cell P {
                webkit-font-smoothing: subpixel-antialiased;
                box-sizing: border-box;
                color: rgb(45, 44, 44);
                display: block;
                font-family: adobe-caslon-pro, Georgia, 'Times New Roman', Times, serif;
                font-size: 18px;
                font-style: normal;
                font-weight: normal;
                height: 28px;
                letter-spacing: normal;
                line-height: 28px;
                margin-bottom: 0px;
                margin-left: 0px;
                margin-right: 0px;
                margin-top: 0px;                
            }
        </style>
    </head>
    <body class="error">
        <div id="error_table">
            <div id="error_cell">
                <div id="container">
                    <h1 id="error_message">Uh Oh... <span>Something went wrong.</span></h1>
                    <p><?php echo $error; ?></p>
                </div>
            </div>
        </div>        
    </body>
</html>