<?php

use Railpage\Images\Images;
use Railpage\Images\Image;
use Railpage\Images\Screener;
use Railpage\Images\Exif;
use Railpage\Images\ImageFactory;
use Railpage\Images\Collection;
use Railpage\Images\Competition;
use Railpage\Images\Competitions;
use Railpage\Images\Utility\CollectionUtility;
use Railpage\Images\Utility\CompetitionUtility;
use Railpage\Users\User;
use Railpage\Images\Collage;

class CompetitionTest extends PHPUnit_Framework_TestCase {
    
    private $ids = [
        "24323758434",
        "24867148435",
        "24567264210",
        "24217963233"
    ];
    
    public function test_createUser($username = "photocomp", $email = "phpunit+photocomp@railpage.com.au") {
        
        $User = new User; 
        $User->username = $username;
        $User->contact_email = $email;
        $User->setPassword(md5(time())); 
        $User->commit();
        $User->setUserAccountStatus(User::STATUS_ACTIVE); 
        
        return $User;
        
    }
    
    /**
     * @depends test_createUser
     */
    
    public function test_createComp($User) {
        
        $Comp = new Competition;
        $Comp->title = "Test competition";
        $Comp->theme = "Test comp theme";
        $Comp->setAuthor($User); 
        $Comp->SubmissionsDateOpen = (new DateTime)->add(new DateInterval("P1D")); 
        $Comp->SubmissionsDateClose = (new DateTime)->add(new DateInterval("P3D")); 
        $Comp->VotingDateOpen = (new DateTime)->add(new DateInterval("P5D")); 
        $Comp->VotingDateClose = (new DateTime)->add(new DateInterval("P10D")); 
        
        $Comp->commit();
        
        $Comp = new Competition($Comp->id); 
        
        $this->assertEquals(Competitions::STATUS_CLOSED, $Comp->status); 
        $this->assertFalse(!filter_var($Comp->id, FILTER_VALIDATE_INT)); 
        $this->assertFalse($Comp->canUserVote($User)); 
        $this->assertFalse($Comp->canUserSubmitPhoto($User)); 
        
        $Comp->SubmissionsDateOpen = (new DateTime)->sub(new DateInterval("P1D")); 
        $Comp->SubmissionsDateClose = (new DateTime)->add(new DateInterval("P3D")); 
        $Comp->VotingDateOpen = (new DateTime)->add(new DateInterval("P5D")); 
        $Comp->VotingDateClose = (new DateTime)->add(new DateInterval("P10D")); 
        
        $this->assertTrue(CompetitionUtility::isSubmissionWindowOpen($Comp)); 
        $this->assertFalse(CompetitionUtility::isVotingWindowOpen($Comp)); 
        
        $Comp->status = Competitions::STATUS_OPEN; 
        $Comp->commit(); 
        
        $DummyUser = new User; 
        $this->assertFalse($Comp->canUserVote($DummyUser)); 
        $this->assertFalse($Comp->canUserSubmitPhoto($DummyUser)); 
        
        return $Comp;
        
    }
    
    /**
     * @depends test_createComp
     */
    
    public function test_competitions($photoComp) {
        
        $Competitions = new Competitions; 
        
        $this->assertEquals(1, count($Competitions->getCompetitions())); 
        $this->assertEquals(0, count($Competitions->getCompetitions(Competitions::STATUS_CLOSED))); 
        $this->assertEquals(0, count($Competitions->getCompetitions(Competitions::STATUS_FUTURE))); 
        $this->assertEquals(1, count($Competitions->getCompetitions(Competitions::STATUS_OPEN))); 
        
        $Competitions->getPreviousContestants(); 
        $Competitions->getScreeners(); 
        $Competitions->getNextCompetition($photoComp); 
        
    }
    
    /**
     * @depends test_createComp
     */
    
    public function test_createPhotos($photoComp) {
        
        $i = 1; 
        
        foreach ($this->ids as $id) {
            $User = $this->test_createUser(sprintf("username-%s", $id), sprintf("phpunit+%s@railpage.com.au", $id)); 
            $Image = ImageFactory::CreateImage($id, "flickr", Images::OPT_REFRESH); 
            
            $this->assertTrue($photoComp->canUserSubmitPhoto($User)); 
            $this->assertFalse($photoComp->isImageInCompetition($Image)); 
            
            $photoComp->submitPhoto($Image, $User); 
            
            $this->assertFalse($photoComp->canUserSubmitPhoto($User));
            $this->assertTrue($photoComp->isImageInCompetition($Image)); 
            
            $this->assertEquals($User->id, $photoComp->getPhotoAuthor($Image)->id);
            
            $this->assertTrue($photoComp->isImageOwnedBy($User, $Image)); 
            
            $photoComp->getNumVotesForUser($User);
            
            $this->assertEquals(1, $photoComp->getNumPendingSubmissions());
            
            $photoComp->approveSubmission($Image); 
            
            $photoComp->getPendingSubmissions();
            $this->assertEquals(0, $photoComp->getNumPendingSubmissions());
            
            $this->assertEquals(0, $photoComp->getNumVotesForImage($Image)); 
            
            $photoComp->getPhotoContext($Image); 
            
            $this->assertEquals(1, $photoComp->getPhoto($Image)->status);
            
            $this->assertEquals($i, count($photoComp->getPhotosAsArray(true))); 
            $i++;
            
        }
        
        $photoComp->getPendingSubmissions(); 
        
        $this->assertEquals(0, $photoComp->getNumPendingSubmissions());
        
        foreach ($this->ids as $id) {
            $Image = ImageFactory::CreateImage($id, "flickr"); 
            $photoComp->rejectSubmission($Image); 
            break;
        }
        
        $photoComp->getArray();
        $photoComp->getPhotosAsArray(); 
        $photoComp->getPhotosAsArrayByVotes(); 
        $photoComp->getSiteMessage(); 
        $photoComp->getWinningPhoto(); 
        
        return $photoComp;
        
    }
    
    /**
     * @depends test_createPhotos
     */
    
    public function test_createCollage() {
        
        $Collage = new Collage;
        $Collage->setDimensions(600, 800)->setDimensions(320, 240); 
        
        foreach ($this->ids as $id) {
            $Image = ImageFactory::CreateImage($id, "flickr");
            $Collage->addImage($Image); 
        }
        
        $Collage->__toString(); 
        
    }
    
    /**
     * @depends test_createPhotos
     * @depends test_createUser
     */
     
    public function test_votePhotos($photoComp, $userObject) {
        
        $photoComp->SubmissionsDateOpen = (new DateTime)->sub(new DateInterval("P10D")); 
        $photoComp->SubmissionsDateClose = (new DateTime)->sub(new DateInterval("P3D")); 
        $photoComp->VotingDateOpen = (new DateTime)->sub(new DateInterval("P1D")); 
        $photoComp->VotingDateClose = (new DateTime)->add(new DateInterval("P10D")); 
        
        $this->assertTrue($photoComp->canUserVote($userObject)); 
        
        $this->assertFalse(empty($photoComp->getPhotosAsArray(true))); 
        
        foreach ($photoComp->getPhotos() as $Photo) {
            
            $this->assertEquals(0, $photoComp->getNumVotesForImage($Photo->Image)); 
            
            $this->assertTrue($photoComp->canUserVote($userObject, $Photo->Image)); 
            $photoComp->submitVote($userObject, $Photo->Image); 
            $this->assertfalse($photoComp->canUserVote($userObject, $Photo->Image)); 
            
            $this->assertEquals(1, $photoComp->getNumVotesForImage($Photo->Image)); 
            
            break;
            
        }
        
        $photoComp->getNumVotesForUser(new User);
        
        $photoComp->SubmissionsDateOpen->sub(new DateInterval("P10W")); 
        $photoComp->SubmissionsDateClose->sub(new DateInterval("P10W")); 
        $photoComp->VotingDateOpen->sub(new DateInterval("P10W")); 
        $photoComp->VotingDateClose->sub(new DateInterval("P10W")); 
        
        $photoComp->getVoteCountsPerDay(); 
        
        $this->assertFalse($photoComp->getWinningPhoto() == false);
        
        
    }

}