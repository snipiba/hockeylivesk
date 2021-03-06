<?
/*
* Funkcia pre výpis najbližších zápasov všetkých líg dňa
* version: 2.0.0 (24.11.2015 - kompletne prekopané pre použitie s novou verziou stránok)
* version: 2.5.0 (6.2.2020 - prispôsobené pre Boostrap 4 template)
* @return $games string
*/

function Get_upcomming() {
  include('slovaks.php');
  $nhl_players=$slovaks;
  $nhl_goalies=$brankari;
  include('slovaki.php');
  $khl_players=$slovaks;
  $khl_goalies=$brankari;
  $dnes = date("Y-m-d", mktime());
  $zajtra = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d')+1, date('Y')));
  $q = mysql_query("SELECT dt.*, position, longname, el FROM 2004leagues JOIN ((SELECT m.id, m.team1short, m.team1long, m.team2short, m.team2long, m.goals1, m.goals2, m.kedy, m.datetime, null as kolo, m.league, t1.id as t1id, t2.id as t2id, t1.longname as t1long, t2.longname as t2long FROM 2004matches m LEFT JOIN 2004teams t1 ON t1.shortname=m.team1short && t1.league=m.league LEFT JOIN 2004teams t2 ON t2.shortname=m.team2short && t2.league=m.league WHERE m.datetime LIKE '$dnes%')
UNION
(SELECT m.id, m.team1short, m.team1long, m.team2short, m.team2long, m.goals1, m.goals2, m.kedy, m.datetime, m.kolo, m.league, t1.id as t1id, t2.id as t2id, t1.longname as t1long, t2.longname as t2long FROM el_matches m LEFT JOIN el_teams t1 ON t1.shortname=m.team1short && t1.league=m.league LEFT JOIN el_teams t2 ON t2.shortname=m.team2short && t2.league=m.league WHERE m.datetime > '$dnes 07:00:00' && m.datetime < '$zajtra 07:00:00'))dt ON dt.league=2004leagues.id ORDER BY position ASC, datetime ASC");

  $games = '<div class="card shadow mb-4">
              <div class="card-header">
                <div class="font-weight-bold text-primary text-uppercase">'.LANG_GAMECONT_TODAYS.'</div>
              </div>
              <div class="card-body">';

  if(mysql_num_rows($q)==0)
    {
    $games .= "<p class='bg-gray-100 border p-2 rounded small'>".LANG_GAMECONT_NOGAMES."</p>
              </div>
            </div>";
    }
  else
    {
    $pos=0;
    $fav="";
    $games .= '<div class="row no-gutters align-items-center">
                <div class="col mr-2">';
    
    while($f = mysql_fetch_array($q))
      {
      if($pos==0) $games .= '
                  <div class="text-xs text-muted font-weight-bold mb-1">'.$f[longname].'</div>
                </div>
              </div>';
      $pos=$f[position];
      if($pos!=$ppos && $ppos) $games .= '
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs text-muted font-weight-bold mb-1 mt-3">'.$f[longname].'</div>
                </div>
              </div>';
      if($pos==0 || ($pos!=$ppos))
        {
        $z = mysql_query("SELECT * FROM el_injuries WHERE league='$f[league]'");
        while($zr = mysql_fetch_array($z))
          {
          $zra[] = $zr[name];
          }
        }
      $slov=$favor="";
      // slovaci v akcii
      if(strstr($f[longname], 'NHL') || strstr($f[longname], 'KHL'))
        {
        if(strstr($f[longname], 'NHL')) { $slovaks = $nhl_players; $brankari = $nhl_goalies; }
        if(strstr($f[longname], 'KHL')) { $slovaks = $khl_players; $brankari = $khl_goalies; }
        $pia1 = array_keys($slovaks, $f[team1short]);
        $gia1 = array_keys($brankari, $f[team1short]);
        $inaction1 = array_merge($pia1, $gia1);
        if(count($zra)>0) $inaction1 = array_diff($inaction1, $zra);
        $inaction1 = array_values($inaction1);
        $pia2 = array_keys($slovaks, $f[team2short]);
        $gia2 = array_keys($brankari, $f[team2short]);
        $inaction2 = array_merge($pia2, $gia2);
        if(count($zra)>0) $inaction2 = array_diff($inaction2, $zra);
        $inaction2 = array_values($inaction2);
        if(count($inaction1)>0 || count($inaction2)>0)
          {
          $c = count($inaction1)+count($inaction2);
          $slov .= '<i class="fas fa-smile" data-toggle="tooltip" data-placement="top" data-html="true" title="';
          $y=0;
          while($y<count($inaction1))
            {
            $slov .= $inaction1[$y].'<br>';
            $y++;
            }
          $y=0;
          while($y<count($inaction2))
            {
            $slov .= $inaction2[$y].'<br>';
            $y++;
            }
          $slov .= '"></i>';
          }
        }
      // favorite team
      if($_SESSION[logged])
        {
        $fa = mysql_query("SELECT user_favteam FROM e_xoops_users WHERE uid='$_SESSION[logged]'");
        $fav = mysql_fetch_array($fa);
        if($fav[user_favteam]!="0" && ($fav[user_favteam]==$f[team1short] || $fav[user_favteam]==$f[team2short])) $favor=' bg-gray-200 rounded';
        }
      // vypis
      $cas = date("G:i", strtotime($f[datetime]));
      if(strtotime($f[datetime]) > mktime()) $score = '<a href="/game/'.$f[id].$f[el].'-'.SEOtitle($f[team1long]." vs ".$f[team2long]).'" class="btn btn-light btn-circle btn-sm"><i class="fas fa-search"></i></a>';
      elseif($f[kedy]!="na programe") $score = '<a href="/report/'.$f[id].$f[el].'-'.SEOtitle($f[team1long].' vs '.$f[team2long]).'" class="font-weight-bold">'.$f[goals1].':'.$f[goals2].'</a>';
      else $score = "$f[goals1]:$f[goals2]";
      $games .= '<div class="row no-gutters align-items-center small'.$favor.'">
                  <div class="col-2 text-nowrap">'.$cas.'</div>
                  <div class="col-3 font-weight-bold text-nowrap"><img class="flag-'.($f[el]==1 ? 'el':'iihf').' '.$f[team1short].'-small" src="/img/blank.png" alt="'.$f[team1long].'"> <a href="/team/'.$f[t1id].($f[el]==1 ? '1':'0').'-'.SEOtitle($f[t1long]).'" data-toggle="tooltip" data-placement="top" title="'.$f[team1long].'">'.$f[team1short].'</a></div>
                  <div class="col-2 text-center">'.$score.'</div>
                  <div class="col-3 text-right font-weight-bold text-nowrap"><a href="/team/'.$f[t2id].($f[el]==1 ? '1':'0').'-'.SEOtitle($f[t2long]).'" data-toggle="tooltip" data-placement="top" title="'.$f[team2long].'">'.$f[team2short].'</a> <img class="flag-'.($f[el]==1 ? 'el':'iihf').' '.$f[team2short].'-small" src="/img/blank.png" alt="'.$f[team2long].'"></div>
                  '.($f[el]==1 ? '<div class="col-2 text-center">'.$slov.'</div>':'').'
                 </div>';
      $ppos = $pos;
      }
    $games .= "</div></div>";
    }
return $games;
}

/*
* Funkcia pre výpis posledných výkonov slovenských hráčov
* version: 1.5.0 (24.11.2015 - funguje na základe starej verzie - prenesené do novej)
* version: 1.6.0 (22.3.2016 - pridanie štatistík aj pre hráčov reprezentácie)
* version: 2.0.0 (6.2.2020 - prispôsobenie pre Boostrap 4 template)
* @return $stat string
*/

function Get_Latest_Stats() {
    function array_orderby()
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
                }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }
  $stat = '<div class="card shadow mb-4">
            <div class="card-header">
                <div class="font-weight-bold text-primary text-uppercase">'.LANG_GAMECONT_STATS.'</div>
            </div>
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">';
  include('slovaks.php');
  $nhl_slovaks = $slovaks;
  include('slovaki.php');
  $slovaks = array_merge($nhl_slovaks, $slovaks);
  $vcera = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d')-1, date('Y')));
  $dnes = date("Y-m-d", mktime());
  array_walk($slovaks, create_function('&$i,$k','$i="\'$k\'";'));
  $in = implode($slovaks,",");
  $w = mysql_query("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';") or die(mysql_error());
  $w = mysql_query("SELECT * FROM el_matches WHERE datetime > '$vcera 07:00' && datetime < '$dnes 07:00' GROUP BY kedy");
  if(mysql_num_rows($w)==1)
    {
    $e = mysql_fetch_array($w);
    if($e[kedy]=="na programe") { $vcera = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d')-2, date('Y'))); $dnes = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d')-1, date('Y'))); }
    }
  $q = mysql_query("(SELECT ft.*, 2004leagues.longname FROM 2004leagues JOIN (SELECT et.* FROM (SELECT el_goals.*, dt.league FROM el_goals JOIN (SELECT id, league FROM el_matches WHERE kedy = 'konečný stav' && datetime > '$vcera 07:00' && datetime < '$dnes 07:00' ORDER BY datetime)dt ON dt.id=el_goals.matchno)et WHERE goaler IN ($in) OR asister1 IN ($in) OR asister2 IN ($in))ft ON 2004leagues.id=ft.league)
  UNION
  (SELECT gt.*, 2004leagues.longname FROM 2004leagues JOIN (SELECT et.* FROM (SELECT 2004goals.*, dt.league FROM 2004goals JOIN (SELECT id, league FROM 2004matches WHERE kedy = 'konečný stav' && datetime > '$vcera 07:00' && datetime < '$dnes 07:00' ORDER BY datetime)dt ON dt.id=2004goals.matchno)et WHERE teamshort='SVK')gt ON 2004leagues.id=gt.league)");
  if(mysql_num_rows($q)==0)
    {
    $stat .= "<p class='bg-gray-100 border p-2 rounded small'>".LANG_GAMECONT_NOSTATS."</p>
              </div>
            </div>
          </div>
        </div>";
    }
  else
    {
    $stats = array();
    $p=0;
    while($f = mysql_fetch_array($q))
      {
      if(!strstr($f[longname], "Tipsport"))
        {
        $gname = $f[goaler];
        $a1name = $f[asister1];
        $a2name = $f[asister2];
        if(array_key_exists($gname,$slovaks) || $f[teamshort]=="SVK") { $stats[$gname][0]++; $stats[$gname][1]++; $stats[$gname][3]=$f[league]; $stats[$gname][4]=$f[longname]; $stats[$gname][5]=$gname; $stats[$gname][6]=$f[teamshort]; }
        if(array_key_exists($a1name,$slovaks) || ($f[teamshort]=="SVK" && $f[asister1]!="bez asistencie")) { $stats[$a1name][0]++; $stats[$a1name][2]++; $stats[$a1name][3]=$f[league]; $stats[$a1name][4]=$f[longname]; $stats[$a1name][5]=$a1name; $stats[$a1name][6]=$f[teamshort]; }
        if(array_key_exists($a2name,$slovaks) || ($f[teamshort]=="SVK" && $f[asister2]!="bez asistencie")) { $stats[$a2name][0]++; $stats[$a2name][2]++; $stats[$a2name][3]=$f[league]; $stats[$a2name][4]=$f[longname]; $stats[$a2name][5]=$a2name; $stats[$a2name][6]=$f[teamshort]; }
        $p++;
        }
      }
      if($p==0) $stat .= "<p class='bg-gray-100 border p-2 rounded small'>".LANG_GAMECONT_NOSTATS."</p>";
      usort($stats, function($a,$b){ $c = $a[3] - $b[3]; $c .= $b[0] - $a[0]; $c .= $b[1] - $a[1]; return $c; });
      /*
      [0] => Array
          (
              [0] => points
              [1] => goals
              [2] => asists
              [3] => lid
              [4] => league longname
              [5] => player
              [6] => teamshort
          )    
      */
    
      $i=$pos=0;
      while($i < count($stats))
        {
        $lid = $stats[$i][3];
        if($stats[$i][1]=="") $stats[$i][1]=0;
        if($stats[$i][2]=="") $stats[$i][2]=0;
        
        if($pos==0) $stat .= '<div class="text-xs text-muted font-weight-bold mb-1">'.$stats[$i][4].'</div>
                            </div>
                           </div>
                           <div class="row no-gutters align-items-center text-xs border-bottom mb-1">
                            <div class="col-9">'.LANG_PLAYERDB_PLAYER.'</div>
                            <div class="col">'.LANG_P.'</div>
                            <div class="col">'.LANG_G.'</div>
                            <div class="col">'.LANG_A.'</div>
                           </div>';
        $pos=$lid;
        if($pos!=$ppos && $ppos) $stat .= ' 
                              <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                  <div class="text-xs text-muted font-weight-bold mb-1 mt-3">'.$stats[$i][4].'</div>
                                </div>
                              </div>
                              <div class="row no-gutters align-items-center text-xs border-bottom mb-1">
                                <div class="col-9">'.LANG_PLAYERDB_PLAYER.'</div>
                                <div class="col">'.LANG_P.'</div>
                                <div class="col">'.LANG_G.'</div>
                                <div class="col">'.LANG_A.'</div>
                              </div>';
        
        $stat .= '            <div class="row no-gutters align-items-center small">
                                <div class="col-9"><img class="flag-iihf '.$stats[$i][6].'-small" src="/img/blank.png" alt="'.$stats[$i][6].'"> '.$stats[$i][5].'</div>
                                <div class="col font-weight-bold">'.$stats[$i][0].'</div>
                                <div class="col">'.$stats[$i][1].'</div>
                                <div class="col">'.$stats[$i][2].'</div>
                              </div>';
        $ppos = $pos;
        $i++;
        }
    $stat .= "</div>
    </div>";
    }
return $stat;
}

/*
* Funkcia pre výpis formy obľúbeného tímu
* version: 1.0.0 (18.10.2016 - vytvorenie funkcie)
* version: 2.0.0 (6.2.2020 - prispôsobenie pre Boostrap 4 template)
* @return $favteam string
*/

function Favourite_Team() {
  $q = mysql_query("SELECT user_favteam FROM e_xoops_users WHERE uid='".$_SESSION[logged]."'");
  $f = mysql_fetch_array($q);
  if($f[user_favteam]!='0')
    {
    $t = mysql_query("SELECT el_teams.longname, cws, cls, el_teams.league, t1.el, t1.longname as liga FROM el_teams JOIN 2004leagues t1 ON t1.id=el_teams.league WHERE shortname = '".$f[user_favteam]."' UNION SELECT 2004teams.longname, cws, cls, 2004teams.league, t1.el, t1.longname as liga FROM 2004teams JOIN 2004leagues t1 ON t1.id=2004teams.league WHERE shortname = '".$f[user_favteam]."' ORDER BY league DESC LIMIT 1");
    $team = mysql_fetch_array($t);
    $w = mysql_query("SELECT team1short, team1long, team2short, team2long, goals1, goals2, kedy, datetime FROM `el_matches` WHERE (team1short='".$f[user_favteam]."' || team2short='".$f[user_favteam]."') && datetime > NOW()
UNION
SELECT team1short, team1long, team2short, team2long, goals1, goals2, kedy, datetime FROM `2004matches` WHERE (team1short='".$f[user_favteam]."' || team2short='".$f[user_favteam]."') && datetime > NOW()
ORDER BY datetime LIMIT 1");
    $e = mysql_query("SELECT team1short, team1long, team2short, team2long, goals1, goals2, kedy, datetime FROM `el_matches` WHERE (team1short='".$f[user_favteam]."' || team2short='".$f[user_favteam]."') && datetime < NOW()
UNION
SELECT team1short, team1long, team2short, team2long, goals1, goals2, kedy, datetime FROM `2004matches` WHERE (team1short='".$f[user_favteam]."' || team2short='".$f[user_favteam]."') && datetime < NOW()
ORDER BY datetime DESC LIMIT 1");
    $favteam = '<div class="card shadow py-2 mb-4">
                  <div class="card-body">
                    <div class="row no-gutters align-items-center">
                      <div class="col mr-2">
                        <div class="h6 font-weight-bold text-primary text-uppercase mb-1">'.LANG_USERPROFILE_FAVTEAM.'</div>
                        <div class="text-xs text-muted font-weight-bold mb-1">'.$team[longname].'</div>
                        <ul class="list-group list-group-flush">';
        if(mysql_num_rows($e)>0)
          {
          $prev = mysql_fetch_array($e);
          if(date("Y-m-d", strtotime($prev[datetime]))==date("Y-m-d", mktime(0,0,0))) $datum='dnes';
          elseif(date("Y-m-d", strtotime($prev[datetime]))==date("Y-m-d", mktime(0,0,0,date("n"),date("j")-1))) $datum='včera';
          elseif(date("Y-m-d", strtotime($prev[datetime]))==date("Y-m-d", mktime(0,0,0,date("n"),date("j")-2))) $datum='pred dvoma dňami';
          else $datum = date("j.n.Y", strtotime($prev[datetime]));
          if($prev[team1short]==$f[user_favteam])
            {
            if($team[el]==1) 
              {
              if(strstr($team[liga], "NHL")) $kde = "vonku";
              else $kde = "doma";
              }
            $skym = $prev[team2long];
            if($prev[goals1]>$prev[goals2])
              {
              $hl = "vyhral $datum $kde nad";
              $score = $prev[goals1].":".$prev[goals2];
              }
            else
              {
              $hl = "prehral $datum $kde s";
              $score = $prev[goals1].":".$prev[goals2];
              }
            }
          else
            {
            if($team[el]==1) 
              {
              if(strstr($team[liga], "NHL")) $kde = "doma";
              else $kde = "vonku";
              }
            $skym = $prev[team1long];
            if($prev[goals1]>$prev[goals2])
              {
              $hl = "prehral $datum $kde s";
              $score = $prev[goals2].":".$prev[goals1];
              }
            else
              {
              $hl = "vyhral $datum $kde nad";
              $score = $prev[goals2].":".$prev[goals1];
              }
            }
          $favteam .= '<li class="list-group-item px-0"><i class="fa-undo-alt fas mr-3 text-gray-400"></i>Naposledy '.$hl.' tímom <strong>'.$skym.'</strong> ('.$score.')</li>';
          }
        if(mysql_num_rows($w)>0)
          {
          $next = mysql_fetch_array($w);
          if($next[team1short]==$f[user_favteam]) 
            {
            if($team[el]==1)
              {
              if(strstr($team[liga], "NHL")) $kde = "vonku";
              else $kde = "doma";
              }
            $skym = $next[team2long];
            }
          else
            {
            if($team[el]==1)
              {
              if(strstr($team[liga], "NHL")) $kde = "doma";
              else $kde = "vonku";
              }
            $skym = $next[team1long];
            }
          if(date("Y-m-d", strtotime($next[datetime]))==date("Y-m-d", mktime(0,0,0))) $datum='dnes '.$kde.' o '.date("H:i", strtotime($next[datetime]));
          elseif(date("Y-m-d", strtotime($next[datetime]))==date("Y-m-d", mktime(0,0,0,date("n"),date("j")+1))) $datum='zajtra '.$kde.' o '.date("H:i", strtotime($next[datetime]));
          elseif(date("Y-m-d", strtotime($next[datetime]))==date("Y-m-d", mktime(0,0,0,date("n"),date("j")+2))) $datum='pozajtra '.$kde.' o '.date("H:i", strtotime($next[datetime]));
          else $datum = date("j.n.Y \o H:i", strtotime($next[datetime]))." ".$kde;
          $favteam .= '<li class="list-group-item px-0"><i class="fa-calendar fas mr-3 text-gray-400"></i>Najbližšie hrá '.$datum.' s tímom <strong>'.$skym.'</strong></li>';
          }

        if($team[cws]==1 || $team[cls]==1) $hl = "začal";
        else $hl = "ťahá";
        if($team[cws]>0) { $co=$team[cws]; if($team[cws]==1) $wl = "výhrou"; else $wl = "výhrami"; $ico = "5"; }
        else { $co=$team[cls]; if($team[cls]==1) $wl = "prehrou"; else $wl = "prehrami"; $ico = "3"; }
        $favteam .= '<li class="list-group-item px-0"><i class="fa-thumbs-up fas mr-3 text-gray-400"></i>Momentálne '.$hl.' sériu s <strong>'.$co.'</strong> '.$wl.'</li>';
      $favteam .= "     </ul>
                      </div>
                    </div>
                  </div>
                </div>";
    }
  return $favteam;
}

/*
* Funkcia pre výpis hráča týždňa
* version: 1.0.0 (26.9.2017 - vytvorenie funkcie)
* version: 2.0.0 (6.2.2020 - prispôsobenie pre Boostrap 4 template)
* @return $potw string
*/

function potw() {
  $potwdata = ComputePOTW();
    
  if($potwdata[0]!=0)
    {
    if($potwdata[1]==1) $q = mysql_query("SELECT p.*, l.longname FROM el_players p LEFT JOIN 2004leagues l ON l.id=p.league WHERE p.id='".$potwdata[0]."'");
    else $q = mysql_query("SELECT p.*, l.longname FROM 2004players p LEFT JOIN 2004leagues l ON l.id=p.league WHERE p.id='".$potwdata[0]."'");
    $f = mysql_fetch_array($q);
    if($potwdata[2]=="") $potwdata[2]=0;
    if($potwdata[3]=="") $potwdata[3]=0;
    $p = $potwdata[2]+$potwdata[3];
    
    if($p==1) $hl = LANG_GAMECONT_POINT;
    else if($p>1 && $p<5) $hl = LANG_GAMECONT_POINTS;
    else $hl = LANG_TEAMSTATS_PTS;
    
    if($f[pos]=="F" || $f[pos]=="LW" || $f[pos]=="RW" || $f[pos]=="CE") $hl1=LANG_PLAYERSTATS_F;
    elseif($f[pos]=="D" || $f[pos]=="LD" || $f[pos]=="RD") $hl1=LANG_PLAYERSTATS_D;
    else $hl1="";
    
    $potw = ' <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">'.LANG_GAMECONT_POTW.'</div>
                      <div class="text-xs text-muted font-weight-bold mb-1">'.$f[longname].'</div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-user-shield fa-2x text-gray-300"></i>
                    </div>
                  </div>
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2 text-center">
                      <img src="data:image/png;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=" data-src="/includes/player_photo.php?name='.$f[name].'" class="lazy rounded-circle img-thumbnail shadow-sm mb-2 p-1" style="width:100px; height:100px; object-fit: cover; object-position: center top;" alt="'.$f[name].'">
                      <p class="m-0 font-weight-bold"><img class="flag-'.($potwdata[1]==1 ? 'el':'iihf').' '.$f[teamshort].'-small align-baseline" src="/img/blank.png" alt="'.$f[teamlong].'"> '.$f[name].'</p>
                      <p class="m-0 text-xs">'.$hl1.'</p>
                      <p class="h5"><span class="badge badge-pill badge-warning">'.$p.' '.$hl.' ('.$potwdata[2].'G + '.$potwdata[3].'A)</span></p>
                    </div>
                  </div>
                </div>
              </div>';
    }
else
    {
    $potw = ' <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">'.LANG_GAMECONT_POTW.'</div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-user-shield fa-2x text-gray-300"></i>
                    </div>
                  </div>
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2 text-center">
                      <p class="mt-4">'.LANG_GAMECONT_NOPOTW.'</p>
                    </div>
                  </div>
                </div>
              </div>';
    }
return $potw;
}

/*
* Funkcia pre výpočet hráča týždňa
* version: 1.0.0 (26.9.2017 - vytvorenie funkcie)
* @return $potwdata array
*/

function ComputePOTW() {
  $z = mysql_query("SELECT * FROM potw WHERE datetime='".date("Y-m-d", mktime())."'");
  if(mysql_num_rows($z)>0)
    {
    $f = mysql_fetch_array($z);
    $potwdata = array($f[pid], $f[el], $f[g], $f[a]);
    }
  else
    {
    $pondelok = date('Y-m-d', strtotime('next Monday -1 week', strtotime('this sunday')));
    $nedela = date('Y-m-d', strtotime('this sunday'));
    $potw = "";
    $w = mysql_query("(SELECT g.*, m.league, 1 as el FROM `el_matches` m JOIN el_goals g ON g.matchno=m.id WHERE datetime > '$pondelok 00:00:00' && datetime < '$nedela 23:59:59')
    UNION
    (SELECT g1.*, m1.league, 0 as el FROM `2004matches` m1 JOIN 2004goals g1 ON g1.matchno=m1.id WHERE datetime > '$pondelok 00:00:00' && datetime < '$nedela 23:59:59')");
    if(mysql_num_rows($w)==0)
      {
      // predosly tyzden
      $pondelok = date('Y-m-d', strtotime('next Monday -1 week', strtotime('last sunday')));
      $nedela = date('Y-m-d', strtotime('last sunday'));
      $w = mysql_query("(SELECT g.*, m.league, 1 as el FROM `el_matches` m JOIN el_goals g ON g.matchno=m.id WHERE datetime > '$pondelok 00:00:00' && datetime < '$nedela 23:59:59')
      UNION
      (SELECT g1.*, m1.league, 0 as el FROM `2004matches` m1 JOIN 2004goals g1 ON g1.matchno=m1.id WHERE datetime > '$pondelok 00:00:00' && datetime < '$nedela 23:59:59')");
      }
    $stats = array();
    /*
    [0] => Array
        (
            [0] => points
            [1] => goals
            [2] => asists
            [3] => lid
            [5] => player
            [6] => teamshort
            [7] => el
        )    
    */
    $p=0;
    while($f = mysql_fetch_array($w))
      {
      $gname = $f[goaler];
      $a1name = $f[asister1];
      $a2name = $f[asister2];
      $stats[$gname][0]++; $stats[$gname][1]++; $stats[$gname][3]=$f[league]; $stats[$gname][5]=$gname; $stats[$gname][6]=$f[teamshort]; $stats[$gname][7]=$f[el]; 
      if($f[asister1]!="bez asistencie") { $stats[$a1name][0]++; $stats[$a1name][2]++; $stats[$a1name][3]=$f[league]; $stats[$a1name][5]=$a1name; $stats[$a1name][6]=$f[teamshort]; $stats[$a1name][7]=$f[el]; }
      if($f[asister2]!="bez asistencie") { $stats[$a2name][0]++; $stats[$a2name][2]++; $stats[$a2name][3]=$f[league]; $stats[$a2name][5]=$a2name; $stats[$a2name][6]=$f[teamshort]; $stats[$a2name][7]=$f[el]; }
      $p++;
      }
    usort($stats, function($a,$b){ $c = $b[0] - $a[0]; $c .= $b[1] - $a[1]; return $c; });
    $teraz = date("Y-m-d", mktime ());
    if($stats[0][7]==1) $e = mysql_query("SELECT * FROM el_players WHERE league='".$stats[0][3]."' && name='".$stats[0][5]."' ORDER BY id DESC LIMIT 1");
    else $e = mysql_query("SELECT * FROM 2004players WHERE league='".$stats[0][3]."' && name='".$stats[0][5]."' ORDER BY id DESC LIMIT 1");
    $r = mysql_fetch_array($e);
    mysql_query("INSERT INTO potw (datetime, pid, el, g, a) VALUES ('$teraz', '".$r[id]."', '".$stats[0][7]."', '".$stats[0][1]."', '".$stats[0][2]."')");
    $potwdata = array($r[id], $stats[0][7], $stats[0][1], $stats[0][2]);
    }
    return $potwdata;
}

/*
* Funkcia pre výpočet zápasu dňa
* version: 1.5.0 (23.11.2015 - funguje na základe starej verzie - prenesené do novej)
* @return $gotdid array (matchid, el)
*/

function ComputeGOTD()
  {
  Global $lid;
  $z = mysql_query("SELECT * FROM gotd WHERE datetime='".date("Y-m-d", mktime())."'");
  if(mysql_num_rows($z)>0)
    {
    $f = mysql_fetch_array($z);
    $gotdid = array($f[matchid], $f[el]);
    }
  else
    {
    $teraz = date("Y-m-d", mktime ());
    $a = mysql_query("SELECT dt.*, et.position FROM ((SELECT team1long, team2long, datetime, league FROM 2004matches WHERE datetime LIKE '$teraz%') UNION (SELECT team1long, team2long, datetime, league FROM el_matches WHERE datetime LIKE '$teraz%') ORDER BY datetime ASC)dt JOIN (SELECT id, position FROM 2004leagues)et ON et.id=dt.league ORDER BY et.position LIMIT 1");
    if(mysql_num_rows($a)==0) 
      {
      $teraz2 = date("Y-m-d H:i:s", mktime ());
      $a = mysql_query("SELECT dt.*, et.position FROM ((SELECT team1long, team2long, datetime, league FROM 2004matches WHERE datetime > '$teraz') UNION (SELECT team1long, team2long, datetime, league FROM el_matches WHERE datetime > '$teraz') ORDER BY datetime ASC)dt JOIN (SELECT id, position FROM 2004leagues)et ON et.id=dt.league ORDER BY dt.datetime, et.position LIMIT 1");
      }
    $f = mysql_fetch_array($a);
    $lid = $f[league];

    // zistenie ci sa jedna o EL
    $q = mysql_query("SELECT * FROM 2004leagues WHERE id='$lid'");
    $f = mysql_fetch_array($q); 
    $teraz = date("Y-m-d H:i:s", mktime ());
    // JEDNA SA O EL
    if($f[el]==1)
      {
      $teamtable = "el_teams";
      if($f[active]==1) $a = mysql_query("SELECT * FROM el_matches WHERE datetime >= '$teraz' && league='$lid' ORDER BY datetime ASC LIMIT 0,1");
      else $a = mysql_query("SELECT * FROM el_matches WHERE kolo='1' && league='$lid' ORDER BY datetime ASC LIMIT 0,1");
      $b = mysql_fetch_array($a);
      $act_round = $b[kolo];
      $teraz = date("Y-m-d", mktime ());
      if($act_round==0) $e = mysql_query("SELECT * FROM el_matches WHERE kolo='0' && datetime LIKE '$teraz%' && league='$lid' ORDER BY datetime ASC");
      else $e = mysql_query("SELECT * FROM el_matches WHERE kolo='$act_round' && league='$lid' ORDER BY datetime ASC");
      $el=1;
      }
    // NEJEDNA SA O EL
    else 
      {
      $teamtable = "2004teams";
      if(!$_GET[sel])
        {
        $a = mysql_query("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';") or die(mysql_error());
        $a = mysql_query("SELECT DATE_FORMAT(datetime, '%Y-%m-%d') as datum FROM 2004matches WHERE league='$lid' GROUP BY datum ORDER BY datetime ASC LIMIT 1");
        $b = mysql_fetch_array($a);
        $_GET[sel] = $b[datum];
        }
      else
        {
        $a = mysql_query("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';") or die(mysql_error());
        $a = mysql_query("SELECT DATE_FORMAT(datetime, '%Y-%m-%d') as datum FROM 2004matches WHERE league='$lid'  && datetime LIKE '$_GET[sel]%' GROUP BY datum ORDER BY datetime ASC LIMIT 1");
        $b = mysql_fetch_array($a);
        }
      $da = explode("-", $b[datum]);
      $hl = "hrací deň - $da[2].$da[1].";
      if(!$uid) $e = mysql_query("SELECT id, team1short, team1long, team2short, team2long, goals1, goals2, pp1, pp2, kedy, t1_pres, t2_pres, goal, datetime, NULL as kolo, next_refresh, league, active, NULL as tip1, NULL as tip2 FROM 2004matches WHERE league='$lid' && datetime LIKE '$_GET[sel]%' ORDER BY datetime ASC");
      else $e = mysql_query("SELECT 2004matches.id, 2004matches.team1short, 2004matches.team1long, 2004matches.team2short, 2004matches.team2long, 2004matches.goals1, 2004matches.goals2, 2004matches.pp1, 2004matches.pp2, 2004matches.kedy, 2004matches.t1_pres, 2004matches.t2_pres, 2004matches.goal, 2004matches.datetime, NULL as kolo, 2004matches.next_refresh, 2004matches.league, 2004matches.active, dt.tip1, dt.tip2 FROM 2004matches LEFT JOIN (SELECT matchid, userid, tip1, tip2 FROM 2004tips WHERE userid='$uid')dt ON (dt.matchid=2004matches.id) WHERE league='$lid' && datetime LIKE '$_GET[sel]%' ORDER BY datetime ASC");
      $el=0;
      }
    // VYPIS ZAPASOV
    $k=0;
    while(list($mid,$t1s, $t1l, $t2s, $t2l, $g1, $g2, $pp1, $pp2, $kedy, $pres1, $pres2, $goal, $datetime, $kolo,  $next_refresh, $league, $act, $tip1, $tip2) = mysql_fetch_array($e)) 
      {
      $i=0;
      $tim = mysql_query("SELECT *, goals-ga as diff FROM $teamtable WHERE league='$lid' ORDER BY body desc, diff desc, goals desc, wins desc, losts asc, ties desc");
      while($i < mysql_num_rows($tim))
        {
        $ti = mysql_fetch_array($tim);
        if($t1s==$ti[shortname])
          {
          $t1p=$i+1;
          break;
          }
        $i++;
        }
      $j=0;
      $tim = mysql_query("SELECT *, goals-ga as diff FROM $teamtable WHERE league='$lid' ORDER BY body desc, diff desc, goals desc, wins desc, losts asc, ties desc");
      while($j < mysql_num_rows($tim))
        {
        $ti = mysql_fetch_array($tim);
        if($t2s==$ti[shortname])
          {
          $t2p=$j+1;
          break;
          }
        $j++;
        }
      $rozdiel = abs($t1p-$t2p);
      $pozicia = round(($t1p+$t2p)/2, 1);
      $vysl = round(($rozdiel+$pozicia)/2, 1);
      $cis[$k] = array($vysl, $mid, $f[el]);
      $k++;
      }

    sort($cis);

    $teraz = date("Y-m-d", mktime ());
    mysql_query("INSERT INTO gotd (datetime, matchid, el) VALUES ('$teraz', '".$cis[0][1]."', '$f[el]')");
    $gotdid = array($cis[0][1], $f[el]);
    }
  return $gotdid;
  }

/*
* Funkcia pre zobrazenie zápasu dňa
* version: 1.5.0 (23.11.2015 - funguje na základe starej verzie - prenesené do novej)
* version: 2.0.0 (3.2.2020 - prispôsobenie pre Boostrap 4 template)
* @return $gotd string
*/

function gotd()
  {
  Global $lid;
  $gotdid = ComputeGOTD();
  
  $gotd = '<div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">'.LANG_CARDS_GOTD.'</div>
                </div>
                <div class="col-auto">
                  <i class="fas fa-hockey-puck fa-2x text-gray-300"></i>
                </div>
              </div>';
  
  if($gotdid[0]!=0)
    {
    if($gotdid[1]==1)
      {
      $gotq = mysql_query("SELECT m.*, DATE_FORMAT(m.datetime, '%e.%c.%Y o %H:%i') as datum, t1.id as t1id, t2.id as t2id, t1.longname as t1long, t2.longname as t2long FROM el_matches m LEFT JOIN el_teams t1 ON t1.shortname=m.team1short && t1.league=m.league LEFT JOIN el_teams t2 ON t2.shortname=m.team2short && t2.league=m.league WHERE m.id='".$gotdid[0]."'");
      $g = mysql_query("SELECT count(id) as poc, ROUND(sum(tip1)/count(id),2) as vys1, ROUND(sum(tip2)/count(id),2) as vys2 FROM el_tips WHERE matchid='".$gotdid[0]."'");
      $k = mysql_query("select userid, komentar, dt.uname as nick from el_tips JOIN (SELECT uid, uname FROM e_xoops_users)dt ON dt.uid=userid where length(komentar) = (select max(length(komentar)) from el_tips WHERE matchid='$gotdid[0]') && matchid='$gotdid[0]'");
      $l = mysql_fetch_array($k);
      }
    else 
      {
      $gotq = mysql_query("SELECT m.*, DATE_FORMAT(m.datetime, '%e.%c.%Y o %H:%i') as datum, t1.id as t1id, t2.id as t2id, t1.longname as t1long, t2.longname as t2long FROM 2004matches m LEFT JOIN 2004teams t1 ON t1.shortname=m.team1short && t1.league=m.league LEFT JOIN 2004teams t2 ON t2.shortname=m.team2short && t2.league=m.league WHERE m.id='".$gotdid[0]."'");
      $g = mysql_query("SELECT count(id) as poc, ROUND(sum(tip1)/count(id),2) as vys1, ROUND(sum(tip2)/count(id),2) as vys2 FROM 2004tips WHERE matchid='".$gotdid[0]."'");
      $k = mysql_query("select userid, komentar, dt.uname as nick from 2004tips JOIN (SELECT uid, uname FROM e_xoops_users)dt ON dt.uid=userid where length(komentar) = (select max(length(komentar)) from 2004tips WHERE matchid='$gotdid[0]') && matchid='$gotdid[0]'");
      $l = mysql_fetch_array($k);
      $st = " shadow-sm";
      }
    $gotf = mysql_fetch_array($gotq);
    $h = mysql_fetch_array($g);
    
    if($gotdid[1]==1) { $mtable = "el_matches"; $ttable = "el_teams"; }
    else { $mtable = "2004matches"; $ttable = "2004teams"; }
    $q = mysql_query("SELECT m.*, DATE_FORMAT(m.datetime, '%e.%c.%Y o %H:%i') as datum, t1.id as t1id, t2.id as t2id, t1.longname as t1long, t2.longname as t2long FROM $mtable m LEFT JOIN $ttable t1 ON t1.shortname=m.team1short && t1.league=m.league LEFT JOIN $ttable t2 ON t2.shortname=m.team2short && t2.league=m.league WHERE m.id='".$gotdid[0]."'");
    
    $gotf = mysql_fetch_array($q);
    if($gotdid[1]==1) $g = mysql_query("SELECT count(id) as poc, ROUND(sum(tip1)/count(id),2) as vys1, ROUND(sum(tip2)/count(id),2) as vys2 FROM el_tips WHERE matchid='".$gotdid[0]."'");
    else $g = mysql_query("SELECT count(id) as poc, ROUND(sum(tip1)/count(id),2) as vys1, ROUND(sum(tip2)/count(id),2) as vys2 FROM 2004tips WHERE matchid='".$gotdid[0]."'");
    $h = mysql_fetch_array($g);
    if($gotdid[1]==1) $k = mysql_query("select userid, komentar, dt.uname as nick from el_tips JOIN (SELECT uid, uname FROM e_xoops_users)dt ON dt.uid=userid where length(komentar) = (select max(length(komentar)) from el_tips WHERE matchid='$gotdid[0]') && matchid='$gotdid[0]'");
    else $k = mysql_query("select userid, komentar, dt.uname as nick from 2004tips JOIN (SELECT uid, uname FROM e_xoops_users)dt ON dt.uid=userid where length(komentar) = (select max(length(komentar)) from 2004tips WHERE matchid='$gotdid[0]') && matchid='$gotdid[0]'");
    $l = mysql_fetch_array($k);
    
    $gotd .= '    <div class="row mb-2 no-gutters">
                    <div class="col-5 text-center">
                      <img src="/images/vlajky/'.$gotf[team1short].'.gif" alt="'.$gotf[team1long].'" class="img-fluid'.$st.'">
                      <div class="gotd-team h6 mb-0 mt-1 font-weight-bold"><a href="/team/'.$gotf[t1id].($gotdid[1]==1 ? '1':'0').'-'.SEOtitle($gotf[t1long]).'" class="stretched-link text-gray-800">'.$gotf[team1long].'</a></div>
                    </div>
                    <div class="col-2 text-center align-self-center">
                      vs.
                    </div>
                    <div class="col-5 text-center">
                      <img src="/images/vlajky/'.$gotf[team2short].'.gif" alt="'.$gotf[team2long].'" class="img-fluid'.$st.'">
                      <div class="gotd-team h6 mb-0 mt-1 font-weight-bold"><a href="/team/'.$gotf[t2id].($gotdid[1]==1 ? '1':'0').'-'.SEOtitle($gotf[t2long]).'" class="stretched-link text-gray-800">'.$gotf[team2long].'</a></div>
                    </div>
                  </div>
                  <div class="text-xs text-center mb-2">
                    <p class="m-0"><span class="font-weight-bold">'.LIVE_GAME_START.':</span> '.$gotf[datum].'</p>
                    <p class="m-0"><span class="font-weight-bold">'.LANG_MATCHES_AVGBET.':</span> '.$h[vys1].' : '.$h[vys2].'</p>
                    <p class="m-0"><span class="font-weight-bold">'.LANG_MATCHES_BETS.':</span> '.$h[poc].'</p>
                  </div>
                  <div class="text-center">
                    <a href="/'.($gotf[active]==1 ? 'report':'game').'/'.$gotf[id].$gotdid[1].'-'.SEOtitle($gotf[team1long].' vs '.$gotf[team2long]).'" class="btn btn-light btn-icon-split">
                      <span class="icon text-gray-600">
                        <i class="fas fa-search"></i>
                      </span>
                      <span class="text text-gray-800">'.($gotf[active]==1 ? LANG_NAV_LIVE:LANG_MATCHES_DETAIL).'</span>
                    </a>
                  </div>';
    }
  else
    {
    if(date("n")>5 && date("n")<9) $gotd .= '<h5 class="mt-2 text-center"><i class="fas fa-umbrella-beach text-gray-300"></i> Letná prestávka</h5><p class="mt-4 text-center">Netrpezlivo čakáme na začiatok novej sezóny ...</p>';
    else $gotd .= '<h5 class="mt-2 text-center"><i class="fas fa-ban text-gray-300"></i> Bez zápasu</h5><p class="mt-4 text-center">Najbližšie nás nečaká žiaden napínavý zápas</p>';
    }
  $gotd .= '  </div>
            </div>';
  return $gotd;
  }
  
/*
* Funkcia pre zobrazenie noviniek
* version: 1.5.0 (29.11.2015 - prerobená stará verzia funkcie do novej podoby stránky)
* version: 2.0.0 (6.2.2025 - prispôsobené pre Boostrap 4 template)
* @param $limit integer - počet noviniek na stránku
* @param $page integer - aktuálna stránka
* @param $topicID integer - zvolené ID kategórie
* @return $newsList string
*/
  
function Get_news($limit, $page,$topicID = false) {

if(!$page) $page=1;
$limit_start = ($page*$limit)-$limit;
if($topicID != false && $topicID!="all")
  {
  $topic = explode("-", $topicID);
  $topicName = substr($topicID, strpos($topicID, "-")+1,strlen($topicID));
  $topicID = $topic[0];
  $specifiedTopic = '&& s.topicid = '.$topicID;
  }
$q = mysql_query("SELECT s.*, t.topic_id, t.topic_title, u.uname, u.name, count(c.id) as comment_count FROM e_xoops_stories s LEFT JOIN e_xoops_topics t ON t.topic_id=s.topicid LEFT JOIN e_xoops_users u ON u.uid=s.uid LEFT JOIN comments c ON c.what='0' && c.whatid=s.storyid WHERE langID = 'sk' $specifiedTopic && s.topicdisplay='1' && s.ihome='0' GROUP BY s.storyid ORDER BY s.published DESC LIMIT $limit_start, $limit");
$i=0;
while ($f = mysql_fetch_array($q)) {
$s="";
$e="";
if($i==2) $newsList .= '<div class="card shadow mb-4">
  <div class="col">
    <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-format="fluid"
         data-ad-layout-key="-ei-13-51-9i+155"
         data-ad-client="ca-pub-8860983069832222"
         data-ad-slot="7800945421"></ins>
    <script>
         (adsbygoogle = window.adsbygoogle || []).push({});
    </script>
  </div>
</div>';
if($f[bodytext] != '') { $s = '<a href="/news/'.$f[storyid].'-'.SEOtitle($f[title]).'">'; $e = '</a>'; }
    preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', $f[hometext], $image);
    preg_match('/<img.+class=[\'"](?P<class>.+?)[\'"].*>/i', $f[hometext], $imageclass);
    $tags_to_strip = Array("img");
    foreach ($tags_to_strip as $tag)
      {
      $f[hometext] = preg_replace("/<\\/?" . $tag . "(.|\\s)*?>/","",$f[hometext]);
      }
    $newsList .= '<div class="card shadow border-left-'.LeagueColor($f[topic_title]).' mb-4">
                    <div class="row no-gutters">
                  
                      <div class="col-md-4 col-lg-2 border-right lazy" data-src="'.$image['src'].'" style="
                          min-height: 200px;
                          background-position: center center;
                          background-repeat: no-repeat;
                          background-size: '.(strstr($imageclass['class'], "news-cover") ? 'cover':'contain').';
                      ">
                      </div>
                      
                      <div class="col-md-8 col-lg-10">
                  
                        <div class="card-header py-3 clearfix">
                          <h6 class="float-left m-0 font-weight-bold text-gray-900"><a href="/news/'.$f[storyid].'-'.SEOtitle($f[title]).'" class="stretched-link text-gray-900">'.$f[title].'</a></h6>
                          <h6 class="float-right m-1 text-xs font-weight-bold text-'.LeagueColor($f[topic_title]).' text-uppercase">'.$f[topic_title].'</h6>
                        </div>
                        <div class="card-body news-body text-justify">
                          '.$f[hometext].'
                          '.($f[bodytext]!="" ? '<p class="float-right small"><a href="#" class="text-'.LeagueColor($f[topic_title]).'">'.LANG_READMORE.' <i class="fas fa-angle-double-right"></i></a></p>':'').'
                          <p class="text-left text-xs text-muted"><span class="font-weight-bold">'.$f[name].'</span> · '.date("j.n.Y H:i",$f[published]).' · <span class="text-hl"><i class="far fa-comment"></i> '.$f[comment_count].'</span></p>
                        </div>
                      
                      </div>
                      
                    </div>
                  </div>';
      $i++;
      }
      
  $pagenext = $page+1;
  $pageprev = $page-1;
  if($topicID != false && $topicID!="all")
    {
    $specifiedTopic = ' WHERE e_xoops_stories.topicid = '.$topicID;
    $tema = $topicID."-".$topicName;
    }
  else $tema = "all";
  $p = mysql_query("SELECT * FROM e_xoops_stories$specifiedTopic");
  $num = mysql_num_rows($p);
  $newsList .= '<nav aria-label="Page navigation">
        <ul class="pagination justify-content-between">
          <li class="page-item">'.($pageprev>=1 ? '<a class="page-link" href="/category/'.$tema.'/'.$pageprev.'"><i class="fas fa-angle-double-left" aria-hidden="true"></i> '.LANG_BACK.'</a>':'').'</li>
          <li class="page-item">'.(($page*4)<=$num ? '<a class="page-link" href="/category/'.$tema.'/'.$pagenext.'">'.LANG_NEXT.' <i class="fas fa-angle-double-right" aria-hidden="true"></i></a>':'').'</li>
        </ul>
      </nav>';
      
  return $newsList;
  }
?>