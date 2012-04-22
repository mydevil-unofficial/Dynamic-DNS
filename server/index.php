<?php
/*
 *  Copyright (c) 2011 Jakub Szafrański <s@samu.pl>
 * 
 *  All rights reserved.
 * 
 *  Redistribution and use in source and binary forms, with or without
 *  modification, are permitted provided that the following conditions
 *  are met:
 *  1. Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *  2. Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in the
 *     documentation and/or other materials provided with the distribution.
 * 
 *  THIS SOFTWARE IS PROVIDED BY THE REGENTS AND CONTRIBUTORS ``AS IS'' AND
 *  ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 *  IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 *  ARE DISCLAIMED.  IN NO EVENT SHALL THE REGENTS OR CONTRIBUTORS BE LIABLE
 *  FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 *  DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 *  OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 *  HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 *  LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 *  OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 *  SUCH DAMAGE.
 */

/*
 * Dynamic DNS for MyDevil server, 0.1
 * Change the DNS entry for a dynamic IP
 */
	error_reporting(0);

	require "config.php";
	if ($_POST['login'] != $config['login'] || $_POST['password'] != $config['password']) die("Invalid login or password.");

	$sock = fsockopen('unix:///tmp/md.sock');
	if (!$sock) die("Cannot connect to unix socket");
	fwrite($sock, "['--json', 'dns', 'list', '{$config['domain']}']");

	$domains = "";

	while (!feof($sock)) {
		$domains .= fgets($sock);
	}
	fclose($sock);
	$domains = json_decode($domains, true);
	$domains = $domains['entries'];
	$found = 0;
	foreach ($domains as $d) {
		//print_r($d);
		if ($d['name'] == $config['entry'] && $d['type'] == 'A') { 
			$found = 1;
			$entry = $d;
		}
	}

	if ($found == 0) die("No entry matched. Initialize dynamic DNS by invoking 'devil dns add {$config['domain']} {$config['entry']} A 127.0.0.1' in your shell accounts prompt.");

	if ($_SERVER['REMOTE_ADDR'] == $entry['content']) die("Your IP address matched the current entry, so no changes will be made.");

	echo "Your current IP {$_SERVER['REMOTE_ADDR']} differs from the current one {$entry['content']}, so I will change it."; 

        $sock = fsockopen('unix:///tmp/md.sock');
        if (!$sock) die("Cannot connect to unix socket");
	fwrite($sock, "['dns', 'del', '{$config['domain']}', '{$entry['id']}']");
	fclose($sock);

        $sock = fsockopen('unix:///tmp/md.sock');
        if (!$sock) die("Cannot connect to unix socket");
        fwrite($sock, "['dns', 'add', '{$config['domain']}', '{$config['entry']}', 'A', '{$_SERVER['REMOTE_ADDR']}', '{$config['ttl']}']");
	fclose($sock);
?>
