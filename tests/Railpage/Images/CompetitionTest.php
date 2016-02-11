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
use Railpage\Users\User;

class CompetitionTest extends PHPUnit_Framework_TestCase {
    
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
        $Comp->description = "This is a test description";
        $Comp->setAuthor($User); 
        $Comp->SubmissionsDateOpen = (new DateTime)->add(new DateInterval("P1D")); 
        $Comp->SubmissionsDateClose = (new DateTime)->add(new DateInterval("P3D")); 
        $Comp->VotingDateOpen = (new DateTime)->add(new DateInterval("P5D")); 
        $Comp->VotingDateClose = (new DateTime)->add(new DateInterval("P10D")); 
        
        $Comp->commit();
        
        $this->assertEquals(Competitions::STATUS_CLOSED, $Comp->status); 
        $this->assertFalse(!filter_var($Comp->id, FILTER_VALIDATE_INT)); 
        $this->assertFalse($Comp->canUserVote($User)); 
        $this->assertFalse($Comp->canUserSubmitPhoto($User)); 
        
        $Comp->SubmissionsDateOpen = (new DateTime)->sub(new DateInterval("P1D")); 
        $Comp->SubmissionsDateClose = (new DateTime)->add(new DateInterval("P3D")); 
        $Comp->VotingDateOpen = (new DateTime)->add(new DateInterval("P5D")); 
        $Comp->VotingDateClose = (new DateTime)->add(new DateInterval("P10D")); 
        
        return $Comp;
        
    }
    
    /**
     * @depends test_createComp
     */
    
    public function test_createPhotos($photoComp) {
        
        $ids = [
            "24323758434",
            "24867148435",
            "24567264210",
            "24217963233"
        ];
        
        foreach ($ids as $id) {
            $User = $this->test_createUser(sprintf("username-%s", $id), sprintf("phpunit+%s@railpage.com.au", $id)); 
            $Image = ImageFactory::CreateImage($id, "flickr"); 
            
            $this->assertTrue($photoComp->canUserSubmitPhoto($User)); 
            $this->assertFalse($photoComp->isImageInCompetition($Image)); 
            
            $photoComp->submitPhoto($Image, $User); 
            
            $this->assertFalse($photoComp->canUserSubmitPhoto($User));
            $this->assertTrue($photoComp->isImageInCompetition($Image)); 
            
            $this->assertEquals($User->id, $photoComp->getPhotoAuthor($Image)->id);
            
            $this->assertTrue($photoComp->isImageOwnedBy($User, $Image)); 
            
        }
        
        $photoComp->getPendingSubmissions(); 
        $this->assertEquals(count($ids), $photoComp->getNumPendingSubmissions());
        
        foreach ($ids as $id) {
            $Image = ImageFactory::CreateImage($id, "flickr"); 
            $photoComp->approveSubmission($Image); 
        }
        
        $this->assertEquals(0, $photoComp->getNumPendingSubmissions());
        
        $photoComp->getArray();
        $photoComp->getPhotosAsArray(); 
        $photoComp->getPhotosAsArrayByVotes(); 
        $photoComp->getSiteMessage(); 
        $photoComp->getVoteCountsPerDay(); 
        
        return $photoComp;
        
    }

}