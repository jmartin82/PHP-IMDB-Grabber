<?php
class DataTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider imdbProvider
     */
    public function testMediaExtraction($imdbId, $expected)
    {
            //create the url
        $imdb_url = 'http://www.imdb.com/title/tt' . $imdbId . '/';
            //get essentian information
        $oIMDB = new IMDB($imdb_url);
        if ($oIMDB->isReady) {
            $this->assertEquals($expected['type'],$oIMDB->getType());
            $this->assertEquals($expected['released'],$oIMDB->isReleased());
            $this->assertEquals($expected['seasons'],$oIMDB->getSeasons());
            $this->assertEquals($expected['genre'],$oIMDB->getGenre());
            $this->assertEquals($expected['runtime'],$oIMDB->getRuntime());
            $this->assertEquals($expected['year'],$oIMDB->getYear());
            $this->assertEquals($expected['title'],$oIMDB->getTitle());
            $this->assertEquals($expected['country'],$oIMDB->getCountry());
            $this->assertEquals($expected['release_date'],$oIMDB->getReleaseDate());
            $this->assertEquals($expected['director'],$oIMDB->getDirector());
            $this->assertEquals($expected['writer'],$oIMDB->getWriter());
            $this->assertEquals($expected['company'],$oIMDB->getCompany());
            $this->assertEquals($expected['description'],$oIMDB->getDescription()); 
             //only test one
            $this->assertEquals($expected['akas'][0],$oIMDB->getAkas()[0]);
            $this->assertEquals($expected['cast'][0],$oIMDB->getCastAndCharacter()[0]);
            $this->assertEquals($expected['languages'],$oIMDB->getLanguages());

            }
            else {
              throw new Exception("Error Processing Request", 1);
          }

      }

      public function imdbProvider()
      {

        //interestellar
        $expected = array();
        $expected['type'] = "movie";
        $expected['released'] = true;
        $expected['seasons'] = 0;
        $expected['genre'] = array('Adventure','Sci-Fi');
        $expected['runtime'] = 169;
        $expected['year'] = 2014;
        $expected['title'] = "Interstellar";
        $expected['country'] = array('USA','UK','Canada');
        $expected['release_date'] = "7 November 2014  (USA)";
        $expected['director'] = array(array('imdb' =>"0634240","name" =>"Christopher Nolan"));
        $expected['writer'] = array(array('imdb' =>"0634300","name" =>"Jonathan Nolan"),array('imdb' =>"0634240","name" =>"Christopher Nolan"));
        $expected['company'] = array( array(
          'imdb'=>'0023400',
          'name'=>'Paramount Pictures'
          ),
        array(
          'imdb'=>'0026840',
          'name'=>'Warner Bros.'
          ),
        array(
          'imdb'=>'0159111',
          'name'=>'Legendary Pictures'
          ));
        $expected['languages'] = array("English");
        $expected['cast'] = array( array(
            "name" => "Ellen Burstyn",
            "imdb" => "0000995",
            "role" => "Murph"
            ),
        array(

            "name" => "Matthew McConaughey",
            "imdb" => "0000190",
            "role" => "Cooper"
            )
        );

        $expected['akas'] = array( array(
          "title" => "Interestelar",
          "country" => "Argentina"
          ),array(
          "title" => "Ulduzlararasi",
          "country" => "Azerbaijan"
          )
          );


        $expected['description'] = "A team of explorers travel through a wormhole in an attempt to ensure humanity's survival.";

        return array(
          array("tt0816692", $expected),
          );
    }
}
?>