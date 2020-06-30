#!/usr/local/php56/bin/php
<?php
require_once "config.php";

dSystem::log('LOW', "Executando periodico...");
cInvite::weeklyInvites(true);
