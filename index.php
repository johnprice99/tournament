<?php
	$entrantsCount = (isset($_GET['entrants'])) ? $_GET['entrants'] : 16;
	$perGroup = (isset($_POST['perGroup'])) ? $_POST['perGroup'] : 4;
	
	$pointsForWin = (isset($_POST['pointsForWin'])) ? $_POST['pointsForWin'] : 3;
	$pointsForDraw = (isset($_POST['pointsForDraw'])) ? $_POST['pointsForDraw'] : 1;
	
	$teamsToPlayoffs = (isset($_POST['teamsToPlayoffs'])) ? $_POST['teamsToPlayoffs'] : 2;
	
	if (!empty($_POST)) {
		unset($_POST['pointsForWin']);
		unset($_POST['pointsForDraw']);
		unset($_POST['perGroup']);
		unset($_POST['teamsToPlayoffs']);
		
		$entrants = array();
		foreach ($_POST as $entrant) {
			$entrants[] = $entrant;
		}
		
		shuffle($entrants);
		$groups = array_chunk($entrants, $perGroup);
	}
?>
<!DOCTYPE HTML>
<html lang="en">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
		<meta name="viewport" content="width=device-width, user-scalable=no" />
		
		<title>Tournament</title>
		
		<?php if (!empty($_POST)) { ?>
			<script src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
			<script>
				var winPoints = <?php echo $pointsForWin; ?>;
				var drawPoints = <?php echo $pointsForDraw; ?>;
				
				var groups = {
					<?php foreach ($groups as $groupNumber => $group) { ?>
						"group<?php echo $groupNumber+1; ?>" : [
							<?php foreach ($group as $entrant) { ?>
								<?php //$maxGames = 6; ?>
								{
									"name" : "<?php echo $entrant; ?>",
									"win" : 0<?php //echo $randomGames = rand(0, $maxGames); $maxGames -= $randomGames; ?>,
									"draw" : 0<?php //echo $randomGames = rand(0, $maxGames); $maxGames -= $randomGames; ?>,
									"loss" : 0<?php //echo $maxGames; ?>,
									"points" : 0
								},
							<?php } ?>
						],
					<?php } ?>
				}
				var groupMatches = new Array;
				var playoffs = new Array;
				
				// Sorting for the points
				jQuery.fn.sort = function() {  
					return this.pushStack( [].sort.apply( this, arguments ), []);  
	    		};
	    		//+ Jonas Raoni Soares Silva
				//@ http://jsfromhell.com/array/shuffle [v1.0]
				shuffle = function(o){ //v1.0
					for(var j, x, i = o.length; i; j = parseInt(Math.random() * i), x = o[--i], o[i] = o[j], o[j] = x);
					return o;
				};
				function chunk(a, s){
				    for(var x, i = 0, c = -1, l = a.length, n = []; i < l; i++)
				        (x = i % s) ? n[c][x] = a[i] : n[++c] = [a[i]];
				    return n;
				}
				//http://ejohn.org/blog/javascript-array-remove/
				Array.prototype.remove = function(from, to) {
					var rest = this.slice((to || from) + 1 || this.length);
					this.length = from < 0 ? this.length + from : from;
					return this.push.apply(this, rest);
				};
				
	
				function sortByPoints(a,b){  
					if (a.points == b.points){
						return a.name > b.name ? 1 : -1; //sort by name otherwise
					}
					return a.points < b.points ? 1 : -1;  
				};  
	    		
	    		//update the points
	    		function updatePoints() {
	    			$.each(groups, function(k, group) {
						$.each(group, function(i, entrant) {
							entrant.points = (entrant.win * winPoints) + (entrant.draw * drawPoints);
						});
						groups[k] = $(group).sort(sortByPoints);
					});
					updateGroupTables();
				}
				function updateGroupTables() {
					var outputString = '';
					var i = 1;
					$.each(groups, function(k, group) {
						outputString += '<h2>Group '+i+'</h2><table><tr><th>Entrant</th><th>P</th><th>W</th><th>D</th><th>L</th><th>Pts</th></tr>';
						$.each(group, function(i, entrant) {
							gamesPlayed = entrant.win + entrant.draw + entrant.loss;
							outputString += '<tr><td>'+entrant.name+'</td><td>'+gamesPlayed+'</td><td>'+entrant.win+'</td><td>'+entrant.draw+'</td><td>'+entrant.loss+'</td><td>'+entrant.points+'</td></tr>';
						});
						outputString += '</table>';
						i++;
						
					});
					$('#groupTables').html(outputString);
				}
				function isArray(obj) {
				    return Object.prototype.toString.call(obj) === "[object Array]";
				}
	
				function setupGroupMatches() {
					//using this algorithm to schedule games - http://en.wikipedia.org/wiki/Round-robin_tournament
					
					$.each(groups, function(k, group) {
						var gamesPerDay = group.length/2;
						var gamesPerTeam = group.length-1;
						
						var homeTeams = new Array;
						$.each(group.slice(0, gamesPerDay), function(k,v) {
							homeTeams.push(v);
						});
						var awayTeams = new Array;
						$.each(group.slice(gamesPerDay), function(k,v) {
							awayTeams.push(v);
						});
						
						for (i=1;i<=gamesPerTeam;i++) {
							
							for (var j=0; j<homeTeams.length;j++) {
								var match = [homeTeams[j], awayTeams[j]];
								groupMatches.push(match);
							}
							
							//move the last element of the home team to the end of the away, and move the first of the away to the second place in the home array
							awayTeams.push(homeTeams[homeTeams.length-1]);
							homeTeams.pop();
							
							homeTeams.splice(1, 0, awayTeams.shift());
						}
	
					});
					outputGroupMatches();
				}
				function outputGroupMatches() {
					var outputString = '<h2>Matches</h2><table border="1">';
					
					groupMatches = shuffle(groupMatches);
					
					$.each(groupMatches, function(k, match) {
						//the rel is the team to remove from the array - not the id of the winning team!
						outputString += '<tr><td id="match'+k+'"><a class="winnerGroupButton" rel="1">'+match[0].name+'</a> vs <a class="winnerGroupButton" rel="0">'+match[1].name+'</a> (<a class="winnerGroupButton" rel="draw">Draw</a>)</td></tr>';
					});
					
					outputString += '</table>';
					$('#groupMatches').html(outputString);
					
					$('.winnerGroupButton').bind('click', function() {
						var self = $(this);
						
						var match = groupMatches[self.parent().attr('id').substring(5)];
						//show the winner
						if (self.attr('rel') != 'draw') {
							self.parent().html(self.html());
							//remove the losing team from the array
							var losingTeam = match[self.attr('rel')];
							match.remove(self.attr('rel'))
							var winningTeam = match[0];
						}
						else {
							match.draw = true;
							self.parent().html(self.html());
							var losingTeam = match[0];
							var winningTeam = match[1];
						}
						
						//loop through all groups until we find this team name
						
						//update the team's win (in the actual group so that the points update correctly)
						//update the losing team (in the actual group so that the points update correctly)
						//update the draw for both teams (in the actual group so that the points update correctly)
						
						$.each(groups, function(k, group) {
							$.each(group, function(i, entrant) {
								if (self.attr('rel') != 'draw') {
									if (entrant.name == winningTeam.name) {
										entrant.win++;
									}
									else if (entrant.name == losingTeam.name) {
										entrant.loss++;
									}
								}
								else {
									if (entrant.name == winningTeam.name || entrant.name == losingTeam.name) {
										entrant.draw++;
									}
								}
							});
						});
						
						updatePoints();
						
						// when there are no matches left, show playoff button
						var moreMatches = false;
						var entrantsLeft = new Array;
						$.each(groupMatches, function(k, match) {
							if(match.length > 1 && match.draw == undefined) {
								moreMatches = true;
							}
						});
						
						if (!moreMatches) {
							$('#proceedToPlayoffs').show();
							$('#groupMatches').hide();
						}
					});
				}
				
				
				function proceedToPlayoffs() {
					$.each(groups, function(k, group) {
						var topEntrants = group.slice(0,<?php echo $teamsToPlayoffs; ?>);
						$.each(topEntrants, function(i,j) {
							playoffs.push(j);
						});
					});
					playoffs = shuffle(playoffs);
					updatePlayoffBracket();
				}
				
				function updatePlayoffBracket() {
					$('#groupTables').hide();
					var outputString = '<h2>Playoffs</h2><table border="1">';
					playoffs = chunk(playoffs, 2);
					$.each(playoffs, function(k, match) {
						//the rel is the team to remove from the array - not the id of the winning team!
						outputString += '<tr><td id="match'+k+'"><a class="winnerButton" rel="1">'+match[0].name+'</a> vs <a class="winnerButton" rel="0">'+match[1].name+'</a></td></tr>';
					});
					outputString += '</table>';
					$('#playoffs').html(outputString);
					
					$('.winnerButton').bind('click', function() {
						var match = playoffs[$(this).parent().attr('id').substring(5)];
						//remove the losing team from the playoffs array
						match.remove($(this).attr('rel'));
						$(this).parent().html($(this).html());
						//when all matches only have one team in them - go through to the next stage, chunk and redraw the brackets
						var moreMatches = false;
						var entrantsLeft = new Array;
						$.each(playoffs, function(k, match) {
							if(match.length > 1) {
								moreMatches = true;
							}
							entrantsLeft.push(match[0]);
						});
						
						if (!moreMatches) {
							if (entrantsLeft.length > 1) {
								playoffs = shuffle(entrantsLeft);
								updatePlayoffBracket();
							}
							else if (entrantsLeft.length == 1) {
								$('#playoffs').html('<h1>'+entrantsLeft[0].name+' wins!!!</h1><p><a href="<?php echo $_SERVER['REQUEST_URI']; ?>">Start Over</a>');
							}
						}
					});
				}
				
				$(function() {
					updatePoints();
					setupGroupMatches();
					
					$('#proceedToPlayoffs').bind('click', function() {
						$(this).hide();
						proceedToPlayoffs();
					});
				});
			</script>
		<?php } ?>
				
		<style>
			/* new clearfix */
			.clearfix:after {
				visibility: hidden;
				display: block;
				font-size: 0;
				content: " ";
				clear: both;
				height: 0;
			}
			* html .clearfix             { zoom: 1; } /* IE6 */
			*:first-child+html .clearfix { zoom: 1; } /* IE7 */
			
			.left { float:left; }
			.right { float:right; }
			.hidden { display:none; }
			th { text-align:left; }
			td, th { padding: 5px 10px; }
			a { cursor:pointer; }
			a:hover { text-decoration:underline; }
			label {
				clear:both;
				width:150px;
			}
			input {
				float:left;
			}
			ul { margin:0; padding:0; }
			li { list-style-type:none; margin-bottom:10px; }
		</style>
	</head>
	<body>
		<?php if (!empty($_POST)) { ?>
			<a id="proceedToPlayoffs" class="hidden">Proceed to playoffs</a>
			
			<div class="clearfix">
				<div id="groupTables" class="left"></div>
				<div id="groupMatches" class="right"></div>
			</div>
			
			<div id="playoffs"></div>
		<?php } else { ?>
			<p style="border:1px solid red; margin-bottom:10px; padding:5px;">GET params:<br />
			<strong>entrants</strong> = amount of entrants (divisible by 4)<br />
			<strong><a href="?autofill">autofill</a></strong> = autofill the text boxes (for testing)</p>
			
			<form action="" method="post">
				<ul>
					<li class="clearfix">
						<label class="left" for="pointsForWin">Points for win</label>
						<input type="text" name="pointsForWin" id="pointsForWin" value="<?php echo $pointsForWin; ?>" />
					</li>
					<li class="clearfix">
						<label class="left" for="pointsForDraw">Points for draw</label>
						<input type="text" name="pointsForDraw" id="pointsForDraw" value="<?php echo $pointsForDraw; ?>" />
					</li>
					<li class="clearfix">
						<label class="left" for="perGroup">Entrants per group</label>
						<input type="text" name="perGroup" id="perGroup" value="<?php echo $perGroup; ?>" />
					</li>
					<li class="clearfix">
						<label class="left" for="teamsToPlayoffs">Teams to playoffs</label>
						<input type="text" name="teamsToPlayoffs" id="teamsToPlayoffs" value="<?php echo $teamsToPlayoffs; ?>" />
					</li>
					<li><hr /></li>
					<?php for ($i=1; $i <= $entrantsCount; $i++) { ?>
						<li class="clearfix">
							<label class="left" for="entrant<?php echo $i; ?>">Entrant <?php echo $i; ?></label>
							<input type="text" name="entrant<?php echo $i; ?>" id="entrant<?php echo $i; ?>"<?php if (isset($_GET['autofill'])) echo ' value="Entrant '.$i.'"'; ?> />
						</li>
					<?php } ?>
					<li>
						<button type="submit">Submit</button>
					</li>
				</ul>
			</form>
		<?php } ?>
	</body>
</html>