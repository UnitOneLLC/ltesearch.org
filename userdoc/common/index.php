<?php
define("LOGOCLASS","logo-350");
/*

    "350MA" : {
      "LOGOCLASS" : "logo-350",
      "IMGPATH" : "350ma",
      "LOGONAME" : "350-logo.png"
      "WGNAME" : "",
      "GROUPEMAIL" : "350ma-cambridge-media-team@googlegroups.com",
      "GROUPHOMEPAGE" : "https://groups.google.com/g/350ma-cambridge-media-team",
      "EMAILSUBJLINE" : "350MA LTE Team"

    },
    "THIRDACT" : {
      "#LOGOCLASS#" : "tam-logo",
      "#IMGPATH#" : "thirdact",
      "#LOGONAME#" : "tam-log.svg"
      "#WGNAME#" : "Massachusetts Working Group",
      "#GROUPEMAIL#" : "thirdactmalte@googlegroups.com",
      "#GROUPHOMEPAGE#" : "https://groups.google.com/g/thirdactmalte"
      "#EMAILSUBJLINE#" : "Third Act MA LTE"      
    }
  }
*/  
?>
<!DOCTYPE html>
<html>
  <head>
    <title>LTE Team Member Guide</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../css/lte.css">
  </head>
  <body>
    
    <!-- Navbar (sit on top) -->
    <div class="w3-top tam-static-head">
      <div class="w3-bar w3-white w3-padding w3-card">
        <img class="<?=LOGOCLASS?>" src="../image/<?=IMGPATH?>/<?=LOGONAME?>" alt="logo">
        <a href="#home" class="w3-bar-item w3-button"><b><?=WGNAME?></b>     Letters-to-the-Editor Team</a>
      </div>
    </div>
    
    <!-- Page content -->
    <div class="w3-content w3-padding" style="max-width:1564px">
      
      <!-- About Section -->
      <div class="w3-container tam-padding-header-top" id="about">
        <h3 class="w3-border-bottom w3-border-light-grey tam-padding-header">The Letters-to-the-Editor Team</h3>
        <p>The primary goal of the Letters-to-the-Editor Team is to generate responses to news and opinion pieces that appear in the local press. Responses are in the form of collaboratively written letters to the editor (LTEs). We want to respond to press items that are related to climate change, energy policy, and environmental justice, for the purpose of amplifying the public's knowledge and awareness of our issues.
        </p>
      </div>
      <div class="w3-container " id="nutshell">
        <h3 class="w3-border-bottom w3-border-light-grey tam-padding-header">In a Nutshell</h3>
        <p>Here's how it works in a nutshell:
          <ol>
            <li>Each day you get an email with links to climate-related stories.</li>
            <li>If any of the stories compel you to write a letter, use the provided tools to write the letter and share it with the team (in "comment" mode).</li>
            <li>You accept or reject the comments from the team as you see fit. </li>
            <li>You email the text to the paper or submit the letter on the paper’s website.</li>
          </ol>
        </p>
        <p>This page may give the appearance that there's a lot to learn, but that's not really the case. Everything that follows is
          very straightforward, and help is always available if you hit a roadblock.
        </p>
      </div>
      
      <div class="w3-container " id="quick-links">
        <h3 class="w3-border-bottom w3-border-light-grey tam-padding-header">Jump to a Topic</h3>
        <ul class="tam-link-list">
          <li><a href="#ggroup">The Google Group</a></li>
          <li><a href="#blast">Alerts (aka “the Morning Blast”)</a></li>
          <li><a href="#draft-page">Creating a draft of your letter</a></li>
          <li><a href="#draft-doc">The Draft Doc</a></li>
          <li><a href="#critique">Let the AI critique your draft</a></li>
          <li><a href="#collab">Sharing Your Draft with the Team</a></li>
          <li><a href="#reviewing">Reviewing and Commenting on Others' Draft Documents</a></li>
          <li><a href="#resolving">Accepting and Rejecting Comments</a></li>
          <li><a href="#sending">Submitting Your Letter to the Paper</a></li>
          <li><a href="#tips">Tips for Getting Your Letter Published</a></li>
        </ul>
      </div>
      
      <div class="w3-container " id="ggroup">
        <h3 class="w3-border-bottom w3-border-light-grey tam-padding-header">The Google Group</h3>
        <p>Membership on the LTE Team is via a Google group, which is similar to a mailing list. The group’s email address is <b><?=GROUPEMAIL?></b>. The home page for the group is <a target="_other" href="<?=GROUPHOMEPAGE?>"><?=GROUPHOMEPAGE?></a>. You can find all the
          messages sent to the group on the home page.
        </p>
        <p>Members of the group will receive the daily email and notifications when members share letters for collaboration. Note that you can adjust your membership in the Google group to receive messages <a href="<?=GROUPHOMEPAGE?>/membership" target="_other">less frequently.</a>
        </p>
      </div>
      
      <div class="w3-container " id="blast">
        <h3 class="w3-border-bottom w3-border-light-grey tam-padding-header">Alerts (aka “the Morning Blast”)</h3>
        <p>Team members get emails, usually one per day, notifying them of articles, op-eds, and editorials that need responses. The alert may include a brief summary of the linked items and may suggest some talking points that a team member could consider using in their response.
        </p>
        <p>Each row in the blast email will be in this form:<br><br>
          <img class="tam-indent" src="../image/row.png"><br><br>
          From left to right, the columns are the following:
          <ul>
            <li>The name of the newspaper the article appeared in.</li>
            <li>A link to the article (in the above example: "Nature in cities is important to environmental resilience").</li>
            <li>A button that links to just the text of the article (used when you don't have access to the paper's website).</li>
            <li>A button that you use to create a draft for a letter about the article.</li>
          </ul>
        </p>
        <p>  
          The <span class="tam-blast-buttons">text</span> button takes you to a page that displays just the text of the article (no pop-ups, paywalls, videos, or ads). It works for most newspapers, but not all. You use this if you can't get to the article by just clicking the link in the second column.
        </p>
        <p>The <span class="tam-blast-buttons">draft</span> button takes you to a new Google Doc that serves as a template for your letter. The letter’s metadata, in a standard format, is at the top of the letter. The metadata makes it easier for the team captains to track letters.
        </p>
      </div>
      
      <div class="w3-container " id="draft-page">
        <h3 class="w3-border-bottom w3-border-light-grey tam-padding-header">Creating a draft of your letter</h3>
        <p>The first step to create a draft of your letter is to click on the <span class="tam-blast-buttons">draft</span> button next to the article link in a row of the morning blast.
          You will then see a page that looks like this:<br><br>
          <img src="../image/create-draft-1.png" class="tam-width-reduced" alt="create draft page screenshot">
        </p>
        <p>The first time you land on this page, you'll need to enter your name in the provided box. </p>
        <p>Notice that on the lower half of the <span class="tam-blast-buttons">draft</span> page, there is a button with the label "Get suggestions".
          If you like, use this button to generate a letter for the chosen article using artificial intelligence. The output is somewhat
          unpredictable. <i>It's possible that what the AI generates will be factually incorrect.</i> It may occasionally generate a high-quality letter, but in most cases you will only want to use the AI-generated text as a starting point for your letter. 
        </p>
        <p>Underneath the "Get suggestions" button, the words "Add instructions" appear. If you click this
          it opens an input box that allows you to provide additional input to the AI. For example, you
          might add something like "Mention that fracking uses an enormous amount of water." The AI will
          incorporate the additional input into the draft it generates. Do this before you click the
          "Get suggestions" button.
        </p>
        <p>The button labeled "Copy" on the lower right copies the AI-generated text to the clipboard so that you can paste it elsewhere.
        </p>
        
        <p>Click the button labeled "Create document" to create the Google Doc that will serve as your draft. The next screen will ask if you want to "make a copy." You do. Click the "Make a Copy" button, and it will open Google Docs.
        </p>
      </div>
      
      <div class="w3-container " id="draft-doc">
        <h3 class="w3-border-bottom w3-border-light-grey tam-padding-header">The Draft Doc</h3>
        <p>Creating the draft document takes you to a Google Doc created just for you.  You will see a page that looks like this:<br><br>
          <img src="../image/draft-head.png" class="tam-width-reduced-more tam-img-border" alt="create draft page screenshot">
        </p>
        <p>You can then simply type (or paste in) the text of your letter below the salutation, "To the editor." Typing in a Google doc is 
          not essentially different than typing an email.
        </p>
        <p>If you are emailing the letter, rather than submitting it to the paper's letter form on its website, your letter may contain hyperlinks.
          <br>
          <br>
          <b style="color:red">DO NOT UNDER ANY CIRCUMSTANCES SUBMIT A LETTER WITH A LINK THAT CONTAINS ltesearch.org</b>
          <br>
        </p>
      </div>
      
      <div class="w3-container " id="critique">
        <h3 class="w3-border-bottom w3-border-light-grey tam-padding-header">Let the AI critique your draft</h3>
        <p>When you think your draft is ready, you can <i>optionally</i> ask the AI to identify the strengths and weaknesses,
           as well as point out errors in grammar, syntax, or spelling.
          <ul>
            <li>Copy the body of your letter (beginning with "To the editor") to the clipboard</li>
            <li>Paste the text into the form at <a target="_other" href="https://ltesearch.org/critique">this page</a>.</li>
          </ul>
        </p>
      </div>

      <div class="w3-container " id="collab">
        <h3 class="w3-border-bottom w3-border-light-grey tam-padding-header">Sharing Your Draft with the Team</h3>
        <p>Google Docs has features that make it easy to allow other team members to review your draft and
          give you some feedback before you send it to the newspaper. Experience has proven that sharing drafts
          leads to more successful LTEs. It also enables to the team captains to track how many letters are being sent and published.
        </p>
        <p>While viewing your draft Google Doc, you will see a button on the upper right of the screen that says "Share." It looks like this:<br><br>
          <img src="../image/share-button-1.png" class="tam-width-reduced-more" alt="share button"> or, on small displays, like this: 
          <img src="../image/share-button-2.png" class="tam-width-reduced-more" alt="share button2"> <br>
        </p>
        <p>Clicking the share button opens this dialog window:<br><br>
          <img src="../image/share-dlg-1.png" class="tam-width-reduced-more" alt="share dialog"> <br>
        </p>
        <p>When you see this, in the box that says "Add people and groups" enter the address of the LTE Google Group: <b><?=GROUPEMAIL?></b> and the press the return key. 
        </p>
        <p>Next you will see this:<br><br>
          <img src="../image/<?=IMGPATH?>/share-dlg-2.png" class="tam-width-reduced-more" alt="share dialog 2"> <br><br>
        </p>
        <p>Now just do the following to complete the sharing process:
          <ol>
            <li><b>VERY IMPORTANT: </b>Click the inverted triangle to the right of the word "Editor" in the box on the right. A menu appears. Choose "Commenter".</li>
            <li>In the large box with the prompt "Message", you can optionally include a message to the team, for example, "All feedback welcome, especially
              on the third paragraph!"</li>
            <li>Click the button labeled "Send" on the bottom right.</li>
          </ol>
        </p>
      </div>
      
      <div class="w3-container " id="reviewing">
        <h3 class="w3-border-bottom w3-border-light-grey tam-padding-header">Reviewing and commenting on others' draft documents</h3>
        <p>When a user shares a draft document, all other team members will receive an email with a subject line like:<br>
          <span class="tam-indent w3-text-grey">[<?=EMAILSUBJLINE?>] Document shared with you: "LTE Boston Globe- Nature in cities is important t"</span>
        </p>
        <p>Clicking on the link in the email opens the Google doc for the letter you're reviewing. Once you're there, any changes you make to the draft will
          be recorded and displayed as changes that the author can either accept or reject. 
        </p>
        <p>Another common practice is to select a section of text in the draft, which will cause this symbol to appear on the right margin:<br>
          <img src="../image/comment.png" class="tam-indent tam-width-reduced-more" alt="comment control icons"> <br>
        </p>
        <p>Click on the upper icon to insert a comment in the margin about the selected text. Click on the lower icon to insert an emoji comment. You can 
          read more about making comments on <a href="https://support.google.com/docs/answer/65129?hl=en" target="_other">Google's support page</a>
        </p>
      </div>
      
      
      <div class="w3-container " id="resolving">
        <h3 class="w3-border-bottom w3-border-light-grey tam-padding-header">Accepting and rejecting comments</h3>
        <p>
          The author of the draft receives email notification of comments and changes to a draft. The author has final say as to which of the suggested changes are kept and which are rejected.
        </p>
        <p>For example, after sharing your draft, you may see a proposed change like this in the right margin of the draft doc:<br><br>
          <img src="../image/sample-comment.png" class="tam-indent tam-width-reduced-more" alt="sample comment bubble"> <br>
        </p>
        <p>If you want to accept the suggested change, click the check mark in the upper right of the comment bubble. If not, click the 'X'. Note that you
          can also reply to the commenter before accepting or rejecting a change.
        </p>
      </div>
      
      <div class="w3-container " id="sending">
        <h3 class="w3-border-bottom w3-border-light-grey tam-padding-header">Submitting Your Letter to the Paper</h3>
        <p>Resolve or reject all comments and edits before submitting your letter. 
        </p>
        <p>
          In the header of the draft document, the address that follows the words "Submit to:" tells you where to submit your letter. There
          are two cases:<br>
          <ol>
            <li>You submit via email. You will notice an at-sign (@) in the address.</li>
            <li>You submit via a form on the newspaper's website. You can just click the address.</li>
          </ol>
        </p>
        <p>
          In both cases, you select and copy the body of the letter to the clipboard. <b>Never submit the document header.</b> 
        </p>
        <p>For email, <b>use your 
          regular email account</b>, and just paste the body of your letter into a new email. Sign the letter with your first and last name, 
          the full address of where you live, and your phone number. The subject line of the email should reference the article or
          editorial you're responding to.
        </p>
        <p>When submitting to a web form, just type in all the requested information and paste in the body of your letter from the draft doc.</p>
      </div>
      
      <div class="w3-container " id="tips">
        <h3 class="w3-border-bottom w3-border-light-grey tam-padding-header">Tips for Getting Your Letter Published</h3>
        <p>Here's some rules of thumb for LTEs.
        </p>
        
        <table class="c12">
          <tbody>
            <tr class="c7">
              <td class="c0 c8" colspan="1" rowspan="1">
                <p class="c9"><span class="c2">Do</span></p>
              </td>
              <td class="c0 c8" colspan="1" rowspan="1">
                <p class="c9"><span class="c2">Don&rsquo;t</span></p>
              </td>
            </tr>
            <tr class="c7">
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Be original. Find an insightful angle that adds to the conversation. (Write a letter you&rsquo;d enjoy reading.)</span></p>
              </td>
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Don&rsquo;t regurgitate the story you&rsquo;re responding to. Editors want reactions, not reporting. </span></p>
              </td>
            </tr>
            <tr class="c7">
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Have an opinion. Editors want your viewpoint, and they want you to support your position with facts.</span></p>
              </td>
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Don&rsquo;t recycle points that were already made in the article you&rsquo;re responding to. Add something the author didn&rsquo;t mention.</span></p>
              </td>
            </tr>
            <tr class="c7">
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Stay focused. If you have five disparate points to make, write five letters.</span></p>
              </td>
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Don&rsquo;t send the exact same letter to multiple papers.</span></p>
              </td>
            </tr>
            <tr class="c7">
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Be concise. Editors love letters that are succinct. For most papers, 200 or 250 words is the limit. For some, it&rsquo;s even less than that.</span></p>
              </td>
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Don&rsquo;t preach or scold. Support your arguments with facts, not raw emotion.</span></p>
              </td>
            </tr>
            <tr class="c7">
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Provide context. Include an introductory sentence that tells the editor what article you&rsquo;re responding to.</span></p>
              </td>
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c15">Don&rsquo;t use ALL CAPS, </span><span class="c11">italics</span><span class="c15">, </span><span class="c14">bold</span><span class="c15">, </span><span class="c10">underline</span><span class="c3">, or emojis. Don&rsquo;t use exclamation points!</span></p>
              </td>
            </tr>
            <tr class="c7">
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Send a plain text email with no attachments.</span></p>
              </td>
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Don&rsquo;t send the letter as an attachment to an email.</span></p>
              </td>
            </tr>
            <tr class="c7">
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Include your contact information: (real) full name, address, and daytime phone. The editor may call to verify that you sent a letter.</span></p>
              </td>
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Don&rsquo;t use sarcasm unless you are positive it will come across as such -- and even then, only in small amounts. Clever digs, pointed or subtle, are only effective if they are not offensive.</span></p>
              </td>
            </tr>
            <tr class="c7">
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Respond on the same day the target article appears, if possible (less important for weekly papers). That said, letters submitted a day later will also likely get consideration.</span></p>
              </td>
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Don&rsquo;t make ad hominem attacks. Criticizing a piece because it was written by someone with an agenda has limited value. You may have an agenda, too.</span></p>
              </td>
            </tr>
            <tr class="c7">
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Respond to articles in your town&rsquo;s local paper to maximize chances of publication.</span></p>
              </td>
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Don&rsquo;t use clich&eacute;s, hyperbole, &uml;sky-is-falling&uml; alarmism. Don&rsquo;t sound apocalyptic.</span></p>
              </td>
            </tr>
            <tr class="c7">
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Customize your letter to show that it was written expressly for the target newspaper.</span></p>
              </td>
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Don&rsquo;t get too wonky. Don&rsquo;t assume the reader is an expert in the issue you&rsquo;re writing about.</span></p>
              </td>
            </tr>
            <tr class="c7">
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Keep the tone of your letter serious but informal. </span></p>
              </td>
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Don&rsquo;t hit &ldquo;send&rdquo; until you&rsquo;ve proofread your letter carefully. An extra set of eyes helps, too.</span></p>
              </td>
            </tr>
            <tr class="c7">
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Include a call for action (e.g. &ldquo;Maura Healey must change this policy&rdquo; or &ldquo;Urge your legislator to support this bill.&rdquo;)</span></p>
              </td>
              <td class="c0" colspan="1" rowspan="1">
                <p class="c1"><span class="c3">Don&rsquo;t worry if your letter isn&rsquo;t published. Every letter you send is a valuable political act.</span></p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- End page content -->
    </div>
    
    
    <!-- Footer -->
    <footer class="w3-center w3-black w3-padding-16">
      <p>Powered by <a href="https://www.w3schools.com/w3css/default.asp" title="W3.CSS" target="_blank" class="w3-hover-text-green">w3.css</a></p>
    </footer>
    
  </body>
</html>

