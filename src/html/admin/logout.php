<?php

setcookie("jwtToken", "", time() - 3600, "/");
header('Location: index.html');