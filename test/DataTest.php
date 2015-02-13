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
            $this->assertEquals($expected['type'],$oIMDB->getType(),"Check Type");
            $this->assertEquals($expected['released'],$oIMDB->isReleased(),"Check IsReleased");
            $this->assertEquals($expected['seasons'],$oIMDB->getSeasons(),"Check Seasons");
            $this->assertEquals($expected['genre'],$oIMDB->getGenre(),"Check Genre");
            $this->assertEquals($expected['runtime'],$oIMDB->getRuntime(),"Check Runtime");
            $this->assertEquals($expected['year'],$oIMDB->getYear(),"Check Year");
            $this->assertEquals($expected['title'],$oIMDB->getTitle(),"Check Title");
            $this->assertEquals($expected['country'],$oIMDB->getCountry(),"Check Country");
            $this->assertEquals($expected['release_date'],$oIMDB->getReleaseDate(),"Check ReleaseDate");
            $this->assertEquals($expected['director'],$oIMDB->getDirector(),"Check Director");
            $this->assertEquals($expected['writer'],$oIMDB->getWriter(),"Check Writer");
            $this->assertEquals($expected['company'],$oIMDB->getCompany(),"Check Company");
            $this->assertEquals($expected['description'],$oIMDB->getDescription(),"Check Description"); 
             //only test one
            
            if(is_array($expected['akas']) && sizeof($expected['akas']) > 0){
              $this->assertEquals($expected['akas'][0],$oIMDB->getAkas()[0],"Check Akas");
            } else {
              $this->assertEquals($expected['akas'],$oIMDB->getAkas(),"Check Akas as empty array");
            }

            if(is_array($expected['cast']) && sizeof($expected['cast']) > 0){
              $this->assertEquals($expected['cast'][0],$oIMDB->getCastAndCharacter()[0],"Check Cast");
            } else {
              $this->assertEquals($expected['cast'],$oIMDB->getCastAndCharacter(),"Check Cast as empty");
            }
            $this->assertEquals($expected['languages'],$oIMDB->getLanguages(),"Check Languages");

            }
            else {
              throw new Exception("Error Processing Request", 1);
          }

      }

      public function imdbProvider()
      {

        //interestellar
        $expectedInterstellar = array();
        $expectedInterstellar['type'] = "movie";
        $expectedInterstellar['released'] = true;
        $expectedInterstellar['seasons'] = 0;
        $expectedInterstellar['genre'] = array('Adventure','Sci-Fi');
        $expectedInterstellar['runtime'] = 169;
        $expectedInterstellar['year'] = 2014;
        $expectedInterstellar['title'] = "Interstellar";
        $expectedInterstellar['country'] = array('USA','UK','Canada');
        $expectedInterstellar['release_date'] = "7 November 2014  (USA)";
        $expectedInterstellar['director'] = array(array('imdb' =>"0634240","name" =>"Christopher Nolan"));
        $expectedInterstellar['writer'] = array(array('imdb' =>"0634300","name" =>"Jonathan Nolan"),array('imdb' =>"0634240","name" =>"Christopher Nolan"));
        $expectedInterstellar['company'] = array( array(
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
        $expectedInterstellar['languages'] = array("English");
        $expectedInterstellar['cast'] = array( array(
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

        $expectedInterstellar['akas'] = array( array(
          "title" => "Interestelar",
          "country" => "Argentina"
          ),array(
          "title" => "Ulduzlararasi",
          "country" => "Azerbaijan"
          )
          );


        $expectedInterstellar['description'] = "A team of explorers travel through a wormhole in an attempt to ensure humanity's survival.";


        //punch (n/A in Tviso)
        $expectedPunch = array();
        $expectedPunch['type'] = "TV Series";
        $expectedPunch['released'] = true;
        $expectedPunch['seasons'] = 1;
        $expectedPunch['genre'] = array('Drama','Romance', 'Thriller');
        $expectedPunch['runtime'] = 0;
        $expectedPunch['year'] = 0;
        $expectedPunch['title'] = "Punch";
        $expectedPunch['country'] = array('South Korea');
        $expectedPunch['release_date'] = "15 December 2014  (South Korea)";
        $expectedPunch['director'] = array();
        $expectedPunch['writer'] = array();
        $expectedPunch['company'] = array( array(
          'imdb'=>'0215344',
          'name'=>'HB Entertainment'
          ));
        $expectedPunch['languages'] = array("Korean");
        $expectedPunch['cast'] = array( array(
            "name" => "Rae-won Kim",
            "imdb" => "0453640",
            "role" => "Park Jung-Hwan"
            ),
        array(

            "name" => "Ah-jung Kim",
            "imdb" => "2098258",
            "role" => "Shin Ha-Gyung"
            )
        );

        $expectedPunch['akas'] = array();


        $expectedPunch['description'] = "n/A";


        //Rubicon (n/A in Tviso's calendar)
        $expectedRubicon = array();
        $expectedRubicon['type'] = "TV Series";
        $expectedRubicon['released'] = true;
        $expectedRubicon['seasons'] = 1;
        $expectedRubicon['genre'] = array('Crime','Drama', 'Mystery', 'Thriller');
        $expectedRubicon['runtime'] = 45;
        $expectedRubicon['year'] = 0;
        $expectedRubicon['title'] = "Rubicon";
        $expectedRubicon['country'] = array('USA');
        $expectedRubicon['release_date'] = "1 August 2010  (USA)";
        $expectedRubicon['director'] = array();
        $expectedRubicon['writer'] = array();
        $expectedRubicon['company'] = array( array(
          'imdb'=>'0183230',
          'name'=>'Warner Horizon Television'
          ),
        array(
          'imdb'=>'0019701',
          'name'=>'American Movie Classics (AMC)'
          ));
        $expectedRubicon['languages'] = array("English");
        $expectedRubicon['cast'] = array( array(
            "name" => "James Badge Dale",
            "imdb" => "0197647",
            "role" => "Will Travers"
            ),
        array(
            "name" => "Jessica Collins",
            "imdb" => "2193754",
            "role" => "Maggie Young"
            )
        );

        $expectedRubicon['akas'] = array( 
          array(
            "title" => "Rubicón",
            "country" => "Spain"
          ),array(
            "title" => "Рубикон",
            "country" => "Russia"
          )
        );


        $expectedRubicon['description'] = "Will Travers is an analyst at a New York City-based federal intelligence agency who is thrown into a story where nothing is as it appears to be.";

        


        return array(
          array("tt0816692", $expectedInterstellar),
          array("tt4329922", $expectedPunch),
          array("tt1389371", $expectedRubicon),
          );
    }
}
?>