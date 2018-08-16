<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class GetLogErrorCommand extends Command
{
protected function configure()
{
    $this
        // the name of the command (the part after "bin/console")
        ->setName('app:show-errors')

        // the short description shown while running "php bin/console list"
        ->setDescription('Show errors from log file.')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp("This command allows you to show errors")
    ;

}

protected function execute(InputInterface $input, OutputInterface $output)
{
    function filter($str)
    {
        $pos = strpos($str, ".");
        $new = substr($str, $pos+1);
        $new = rtrim ($new,":");
        return $new;
    }

    function TypeOfErrors($str1,$str2)
    {
        if ($str1 == "ERROR")
        {
            $resultat1 = strstr($str2, 'ERROR:');
            return $resultat1;
        }
        else if ($str1 == "CRITICAL")
        {
            $resultat1 = strstr($str2, 'CRITICAL:');
            return $resultat1;
        }
        else
            return "";
    }


    $CurrentDate = date("Y-m-d H:i:s");

    //Retourne une date sous forme d'une chaîne, au format donné par le paramètre

    $date1=date_create($CurrentDate);

    //Retourne un objet DateTime


    $lines = array();
    $Verif = array();

    $direction ='./var/logs/test1.log';



    foreach (file($direction) as $line) {


        $parts = explode(' ', $line);

        $domain = TypeOfErrors(filter($parts[2]),$line);

        $LogTime=ltrim($parts[0], '[') . ' ' . rtrim($parts[1], ']');

        $date2=date_create($LogTime);

        $diff=date_diff($date1,$date2);

        $convert = $diff->format('%y-%m-%d-%h-%i-%s');

        $T = explode('-',$convert);



        if ($T[0]=='0' && $T[1]=='0' && $T[2]=='0' && $T[3]=='0' && intval($T[4])<= 10

            && (filter($parts[2])== "ERROR" || filter($parts[2])== "CRITICAL" ))
        {

            if (in_array($domain,$Verif)){
                //Indique si une valeur appartient à un tableau
                //Retourne TRUE si valeur est trouvé dans le tableau, FALSE sinon.

                continue;
            }
            else{
                $Verif[] = $domain ;
                $lines[] = array(
                    'date' => $LogTime,
                    'desc' => $domain,
                    'type' => rtrim ($parts[2],":")
                );
            }
        }
    }

    if (!empty ($lines))
        //Détermine si une variable est vide
        //Retourne FALSE si var existe et est non-vide
    {
        foreach ($lines as $line) {


            $data1 = array(
                "issue" => array("subject" => $line['type'],
                                 "description" => $line['desc'],
                                 "priority_id" => 1
                                ),
                "project_id" => 1
            );

            $output = json_encode($data1);

            $ch = curl_init('http://localhost:8081/issues.json?key=562fb552d90c6f0d4894385441d1cb564ec7c440');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $output);


            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($output))
            );

            $resp = curl_exec($ch);
            curl_close($ch);
        }
    }
}
}