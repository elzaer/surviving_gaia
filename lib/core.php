<?php
Namespace Core
/** LIBRARY CORE CLASS **/

# PRIME NUMBER TEST
function is_prime($n) 
{
  for($i=$n;--$i&&$n%$i;);
  return $i==1;
}

# EVEN NUMBER TEST
function is_even($n)
{
  return $n%2==0;
}

class Console extends DBConfig\DB
{
  
}
