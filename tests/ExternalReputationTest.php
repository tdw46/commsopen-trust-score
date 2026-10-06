<?php
require __DIR__.'/../src/TrustScore.php';
use CommsOpen\Trust\TrustScore;
$n=0;function rep_ok($condition,$message){global $n;if(!$condition)throw new RuntimeException($message);$n++;}
$base=array('accountAgeDays'=>365,'profileCompleteness'=>1,'commentsAuthored'=>8);$ordinary=TrustScore::calculate($base);
foreach(array(0,1,10,100,1000,10000,100000,PHP_INT_MAX)as$rep){$r=TrustScore::calculate($base+array('externalReputationVerified'=>true,'stackExchangeReputation'=>$rep,'stackOverflowReputation'=>$rep));rep_ok($r['score']>=$ordinary['score'],'Reputation never lowers existing score');rep_ok($r['externalReputation']['adjustment']>=0&&$r['externalReputation']['adjustment']<=10,'Bounded positive bonus');rep_ok($r['longTerm']===$ordinary['longTerm']&&$r['recent']===$ordinary['recent'],'Existing denominators and community factors stay intact');}
$unverified=TrustScore::calculate($base+array('stackOverflowReputation'=>999999));rep_ok($unverified['score']===$ordinary['score']&&!$unverified['externalReputation']['active'],'Unverified counts do nothing');
$negative=TrustScore::calculate($base+array('externalReputationVerified'=>true,'stackExchangeReputation'=>-100,'stackOverflowReputation'=>-1));rep_ok($negative['score']===$ordinary['score'],'Negative reputation is neutral');
$prev=0;foreach(array(1,10,100,1000,10000,100000)as$rep){$r=TrustScore::calculate($base+array('externalReputationVerified'=>true,'stackOverflowReputation'=>$rep));rep_ok($r['externalReputation']['adjustment']>=$prev,'Monotonic bonus');$prev=$r['externalReputation']['adjustment'];}
$onlySO=TrustScore::calculate($base+array('externalReputationVerified'=>true,'stackOverflowReputation'=>100000));rep_ok($onlySO['externalReputation']['adjustment']===5.0,'One platform capped at five points');
$recent=$base+array('recentEvidenceCount'=>10,'recentWarnings'=>5,'recentModerationPenalties'=>60);$r=TrustScore::calculate($recent+array('externalReputationVerified'=>true,'stackOverflowReputation'=>99999));rep_ok($r['recent']===TrustScore::calculate($recent)['recent'],'External reputation preserves recent behavior adjustments');
echo "STACK_REPUTATION_ALGORITHM_OK $n checks\n";
