\NeedsTeXFormat{LaTeX2e}
\ProvidesClass{Class_Files/Welly_Workbook}
\LoadClass[12pt,a4paper]{Class_Files/exam}

%Class Version
\newcommand{\classvers}{Class Version 1.0.1}

%Packages 
\RequirePackage{graphicx} %Allows for the wellington logo
\graphicspath{{Class_Files/images/}} %Lets the computer find the logo
\RequirePackage[T1]{fontenc} %the font                
\RequirePackage[oldstyle]{Class_Files/packages/libertine} %more about the font         
\RequirePackage[libertine]{Class_Files/packages/newtxmath} %Even more about the font         
\RequirePackage{tikz} %this is for diagrams                       
\RequirePackage[subpreambles=true]{Class_Files/packages/standalone} %to be able to import other files into this document
\RequirePackage{Class_Files/packages/import} %to allow for the preprepared preamble files to be imported    
%\RequirePackage{hieroglf} 
%\RequirePackage{Class_Files/packages/pgfplots}


\RequirePackage{pgf,tikz} %This is for diagrams
\usetikzlibrary{arrows} %Allows arrows to be used in TikZ
\usetikzlibrary{patterns} %Allows patterns to be used in TikZ
\usetikzlibrary{shapes.misc}
\usetikzlibrary{shapes}
\usetikzlibrary{decorations.markings}

\RequirePackage{mathrsfs} %Allows various mathematical symbols to be used
\RequirePackage{amsmath} %Allows various mathematical symbols to be used
\RequirePackage{Class_Files/packages/cancel} %Allows you to print crossed out letters (to show cancelling in working)
\RequirePackage{gensymb}  %Allows various mathematical symbols to be used
\RequirePackage{mathtools} %Allows for varoius other mathematical symbols to be used
\RequirePackage[hidelinks]{hyperref}  %All the hyperlinked cross references in the document  
\RequirePackage{color} %Allows colours to be used throughout the document
\RequirePackage{tabularx} %Allows formatting of tables
\RequirePackage{multicol} %Allows 
\RequirePackage{Class_Files/packages/tabstackengine} %Allows for \alignshortunderstack environment used to align maths
\RequirePackage{Class_Files/packages/bigints} %Allows for bigger integrals  - NRC's favourite 
\RequirePackage{Class_Files/packages/mathdots} %Allows for various dots in maths mode
\RequirePackage{hhline} %Allows a double line when formatting a table 
\RequirePackage{ifthen} %Provides commands used for conditional commands
\RequirePackage{xparse} %enables NewDocumentCommand, needed for advanced glossary commands.


%Document structure
\newcommand{\qref}[1]{\mbox{\nameref{#1}--\ref{#1}}}
\newcommand{\sqref}[1]{\mbox{[\nameref{#1}--\ref{#1}]}}

\newcommand{\aref}[1]{\abox[0]{\ref{#1}}\ignorespaces} %inserts an answer reference for a question which starts without parts
\newcommand{\apref}[1]{\abox[22.5]{\ref{#1}}\ignorespaces} %inserts an answer reference for a question which starts with a part
\newcommand{\abox}[2][0]{
\begin{tikzpicture}[x=1pt,y=1pt]
\draw[use as bounding box, color = white] (0,0) rectangle (0,12);
\draw (460-#1,6.25) node[left,red] {\phantom{$\bigint$}\emph{#2}};
\end{tikzpicture}} %inserts answer reference box in the right margin. Compulsory argument - the reference for the question. Optional argument - an additional indent.


\newif\ifforcetwopage
\newcommand{\nextprobset}[1]{
\ifforcetwopage
\oddeven{\newpage \section*{\centering{#1}}}{\newpage \mbox{} \newpage \section*{\centering{#1}}}
\else
\newpage \section*{\centering{#1}}
\fi
} 
\newcommand{\probset}[1]{
\ifforcetwopage
\oddeven{\newpage \section*{\centering{#1}}}{\newpage \mbox{} \newpage \section*{\centering{#1}}}
\else
\newpage \section*{\centering{#1}}
\fi
}

\newcommand{\skippage}{\newpage\thispagestyle{empty}\mbox{}\newpage}

\newcommand{\newsec}[1]{\section*{#1}\addcontentsline{toc}{section}{#1}}

\newcommand{\frontcover}[3]{
\thispagestyle{empty}
\begin{center}
\includegraphics{Welly}\\
\vspace{0.3in}
\huge\textsc{{Wellington College}}\\
\huge\textsc{{Mathematics Department}}\\
\vspace{1in}
\textbf{\LARGE{
\ifprintanswers
Course Companion
\else
Coursebook
\fi
}}\\
\vspace{0.3in}
\textbf{\LARGE{#1\\
#2}}\\
\vspace{1.5in}
\normalsize{Wellington College, #3}
\end{center}
}

\newcommand{\testfrontcover}[2]{
\thispagestyle{empty}
\begin{center}
\includegraphics{Welly}\\
\vspace{0.3in}
\huge\textsc{{Wellington College}}\\
\huge\textsc{{Mathematics Department}}\\
\vspace{1in}
\vspace{0.3in}
\textbf{\LARGE{#1\\
Test Book}}\\
\vspace{1.5in}
\normalsize{Wellington College, #2}
\end{center}
}

\newcommand{\setupfooter}[2]{
\lfoot{\oddeven{#1}{#2}}                          
\rfoot{\oddeven{#2}{#1}}                         
\cfoot{\oddeven{\thepage}{\thepage}}     
}

\renewcommand{\subpartlabel}{(\thesubpart)} %formatting so that subparts are print: (i)

\newcommand{\gloss}[2]{
\noindent\mbox{%
    \minipage[t]{\dimexpr0.3\linewidth-2\fboxsep-2\fboxrule}
        \textbf{#1}
    \endminipage}\hfill
    \mbox{%
    \minipage[t]{\dimexpr0.6\linewidth-2\fboxsep-2\fboxrule}
        #2
    \endminipage}

\bigskip
}

\NewDocumentCommand{\glossent}{mO{#1}}{\hyperlink{gloss:#2}{\emph{#1}}}
\NewDocumentCommand{\glossitem}{mmO{#1}}{\gloss{\hypertarget{gloss:#3}{#1}}{#2}}

\newif\ifprintdiagrams

\newcommand{\diagram}[1]{
\begin{center}
\ifprintdiagrams
#1
\else
\noindent\fbox{\parbox[c][15pt][c]{\dimexpr\textwidth-2\fboxsep-2\fboxrule}
       {\begin{center}
       This is a placeholder for a diagram
       \end{center} }}
\fi
\end{center}
}

\newcommand{\licence}[1]{
\noindent Please note that copyright in this work is owned by Wellington College and this copy of\\ ``#1''  is provided subject to the terms and conditions set out below. Your use of this book shall be deemed to constitute your acceptance of these terms. In consideration of your agreement to comply with the terms of this licence, Wellington College hereby grants a royalty-free, non-exclusive, non-transferable licence on the terms set out below:\\
You may:
\begin{itemize}

\item Make reasonable number of copies of the whole or part of this book (the ``Work'') solely for the purpose of teaching in your school provided that either;
\item less than 10\% of the Work is reproduced; or
\item the Work is reproduced in its entirety, including the cover page, copyright notice, glossary and appendices.
\end{itemize}
You shall:
\begin{itemize}
\item Always ensure that the copyright footer is displayed on the Work.
\item Ensure that the Work is attributed to Wellington College.
\item Notify Wellington College if you intend to teach using the Work.
\end{itemize}
You shall not:
\begin{itemize}
\item Adapt the Work in any way. 
\item Distribute the whole or any part of the Work other than the distribution of the Work by teachers to students for the purpose of teaching in your school.
\item Sub-license the Work.
\item Make any other use of the Work other than as permitted above.
\end{itemize}
Given that this licence is provided on a royalty-free basis, please note that Wellington College accepts no liability in respect of any claims in relation to the contents of the Work.\\
}




\newcommand{\helpO}{
\begin{enumerate}
\item Be patient
\begin{enumerate}
\item Just because you understand something, doesn't mean everyone else does.
\item If you can't explain what you think you understand, you haven't mastered it yet. If people in your group still don't understand you haven't explained it well enough.
\end{enumerate}
\item Be honest
\begin{enumerate}
\item If someone makes a mistake, point it out kindly.
\item If an explanation isn't helping, ask for greater clarity.
\end{enumerate}
\item Ask, don't tell
\begin{enumerate}
\item People make better deductions when they work something out for themselves.
\item See if you can help someone work something out by asking the right questions.
\end{enumerate}
\item Remember your audience
\begin{enumerate}
\item There's no point in explaining something if you leave your audience behind.
\item Check that they understand by asking them directly, and by asking questions that test their understanding.
\end{enumerate}
\item Answer the right question
\begin{enumerate}
\item When someone asks why something works, give them justification.
\item When someone asks how you knew to do what you did, explain your thinking.
\item Understand that these are different, and try to ask the right question, too.
\end{enumerate}
\item Don't be a jerk
\begin{enumerate}
\item People are more likely to listen to your ideas if you put them across politely.
\item Nobody benefits if you mock someone for making a mistake.
\item Nobody likes a know-it-all.
\end{enumerate}
\end{enumerate}
}

\newcommand{\helpU}{
\begin{enumerate}
\item Be patient
\begin{enumerate}
\item Harkness learning in maths is about developing your knowledge over time. With the right study habits, you will eventually succeed.
\item Don't be cross with yourself or others if you don't get things straightaway. Instead, think about what you can do to help yourself improve.
\end{enumerate}
\item Be honest
\begin{enumerate}
\item If you don't understand something, say.
\item If an explanation isn't helping, ask for greater clarity.
\item If you have worked collaboratively, say so, and acknowledge where your partner may be covering gaps in your knowledge.
\end{enumerate}
\item Interrogate
\begin{enumerate}
\item If you're not sure why something is true, how are you sure that it is true? Make sure you can explain every step as you go along.
\item If you think you have the right answer but aren't sure whether your solution is correct, ask someone else to try to find holes in it for you.
\end{enumerate}
\item Be prepared
\begin{enumerate}
\item It's no fun if everyone is talking about the latest episode of something but you haven't seen it yet. Make sure you have understood what all the questions are asking before you come to class.
\item You might not solve all the problems on the a sheet but you should at least try them out. Draw a diagram, summarize the given information, to try to relate to a problem you have already solved.
\item Assess your own work. If you think you have got a good answer, review it so you know what its strengths and weaknesses are.
\end{enumerate}
\item Catch up
\begin{enumerate}
\item If you miss a lesson, you still need to tackle the assigned problems.
\item If you learn in class how to solve a problem you didn't manage to complete for prep, go back in your own time and re-do it.
\end{enumerate}
\item Be involved
\begin{enumerate}
\item This learning journey is a team effort. You will make better progress if you join in.
\item Nobody likes to ride a tandem when one person won't pedal. Your contributions to class are valuable no matter how much you do or don't understand of the question at hand. Be sure to share your ideas.
\end{enumerate}
\end{enumerate}
}

\newcommand{\howTo}[1]{
\noindent This book has been designed to help you master all the required content for #1. It is not structured like most maths courses you have previously taken, and you will find it requires a different approach on your part. \\ \linebreak
Each lesson you will be set a number of problems---usually between 8 and 10---to prepare for the next lesson. They should take 40-50 minutes in total. You might not always be able to fully solve all problems, but we expect you to attempt as many as you can. In class, you will be expected to present your solutions and discuss solutions that others have shared. Lessons are your principal opportunity to ask for help on material you couldn't do, so be prepared to ask other students how they solved problems or how they knew what approach to take. Discussion should take about 40 minutes in your lessons, and the remainder of the time will be spent looking at problems from the review exercises.\\ \linebreak
\notebook[-4.6]How you take notes is very much up to you, but we have put icons in the margin whenever we think there is a topic on which you are likely to want to take notes. If you are unsure what you are supposed to have understood from a question, ask your peers.\\ \linebreak
Some questions include references to previous questions in square brackets like this: \mbox{[XXV-4]}. This means that the content of the question follows on from the material in problem 4 from sheet 25. If you don't know straightaway how to tackle the problem you are looking at, you might like to follow the reference to see if it sheds any light on the problem.\\ \linebreak
The back of the book contains a glossary of terms---some familiar, some unfamiliar. Terms that appear in italics in the text appear in the glossary, as do a number of general terms which you are expected to know.\\ \linebreak
Each lesson will involve discussing the problems you have prepared for homework in preparation for the next sheet of problems. Don't try to get ahead, therefore, as it will render the discussion less valuable. If you have spare time and want to do more, it is better to look at old questions and satisfy yourself that you really understand them.\\ \linebreak
You will have a fortnightly test, lasting 40 minutes and worth 30 marks. Use your textbook, or the review exercises provided, to help you prepare. \\ \linebreak
Please complete all your solutions in one place, either in an electronic workbook which is shared with your teacher, or in the notebook provided by your teacher. Although your work will not be taken in for regular assessment, you should always be ready to show it to your teacher.
}

\newcommand{\makeMost}{
\noindent Your learning will happen in two separate ways. First, you will learn by tackling unfamiliar questions. By applying techniques you know to a new scenario, or with slightly more algebraic complication, your understanding will develop incrementally. You will also learn in class, from your classmates, as they explain the solutions they have developed. You might see a new way of solving a problem that you hadn't thought of. Assimilating this technique into your toolkit may help you solve new problems in the future.
}

\newcommand{\sectioncover}[1]{
\vspace*{\fill}
\begin{center}
\newsec{#1}
\end{center}
\vfill
}

%Question architecture
\renewcommand{\solutiontitle}{}
\newcommand{\solns}[1]{\begin{solution} \textbf{Solution:}\enspace{#1} \end{solution}}
\newcommand{\solnc}[1]{\begin{solution} \textbf{Commentary:}\enspace{#1} \end{solution}}
\newcommand{\solnsc}[2]{\begin{solution}\textbf{Solution:}\enspace{#1} \mbox{}\\ \textbf{Commentary:}\enspace{#2}\end{solution}}
\newcommand{\solncs}[2]{\begin{solution}\textbf{Commentary:}\enspace{#1} \mbox{}\\ \textbf{Solution:}\enspace{#2}\end{solution}}

\newcommand{\solnsq}[2]{\begin{solution} \textbf{Solution:}\enspace{#1} \mbox{}\\ \textbf{Questions:}\enspace{#2} \end{solution}}
\newcommand{\solncq}[2]{\begin{solution} \textbf{Commentary:}\enspace{#1} \mbox{}\\ \textbf{Questions:}\enspace{#2} \end{solution}}
\newcommand{\solnscq}[3]{\begin{solution}\textbf{Solution:}\enspace{#1} \mbox{}\\ \textbf{Commentary:}\enspace{#2} \mbox{}\\ \textbf{Questions:}\enspace{#3}\end{solution}}
\newcommand{\solncsq}[3]{\begin{solution}\textbf{Commentary:}\enspace{#1} \mbox{}\\ \textbf{Solution:}\enspace{#2} \mbox{}\\ \textbf{Questions:}\enspace{#3}\end{solution}}

\newcommand{\hint}[1]{\\ \begin{small}
\textbf{Hint:}\enspace\emph{#1}
\end{small}}


\newcommand{\twocol}[1]{\begin{multicols}{2} #1 \end{multicols}}
\newcommand{\threecol}[1]{\begin{multicols}{3} #1 \end{multicols}}
\setlength\multicolsep{1pt}

\newcommand{\notebook}[1][0]{\begin{tikzpicture}[line cap=round,line join=round,>=triangle 45, x=0.15cm, y=0.15cm]
\draw[use as bounding box,color = white] (11+#1,2.5) rectangle (11+#1,3);
\draw (0,0) rectangle (4,6);
\foreach \y in {1,2,3,4,5}
	\draw (0.5,\y*0.9)--(3.5,\y*0.9);
\foreach \r in {0.5,1.5,2.5,3.5}
	{\draw (\r,5.5) arc (-90:180:0.25 and 0.5);
	\draw (\r,5.5) circle (0.1);}
\end{tikzpicture}}
\newcommand {\notes}{\notebook}
\newcommand {\partnotes}{\notebook[5.3]}

%Maths
\newcommand{\mS}{\mbox{ms\textsuperscript{-1}}}
\newcommand{\mSS}{\mbox{ms\textsuperscript{-2}}}
\newcommand{\nM}{\mbox{Nm\textsuperscript{-1}}}

\newcommand{\dy}{\mathrm{d}y}
\newcommand{\dx}{\mathrm{d}x}
\newcommand{\dt}{\mathrm{d}t}
\newcommand{\dv}{\mathrm{d}v}
\newcommand{\diff}[2]{\dfrac{\mathrm{d}#1}{\mathrm{d}#2}}
\newcommand{\sdiff}[2]{\tfrac{\mathrm{d}#1}{\mathrm{d}#2}}
\newcommand{\du}{\mathrm{d}u} 

\newcommand{\dydx}{\dfrac{\dy}{\dx}}
\newcommand{\dudx}{\dfrac{\du}{\dx}}
\newcommand{\dvdx}{\dfrac{\dv}{\dx}}
\newcommand{\dydt}{\dfrac{\dy}{\dt}}
\newcommand{\dtdx}{\dfrac{\dt}{\dx}}
\newcommand{\dxdt}{\dfrac{\dx}{\dt}}
\newcommand{\ddx}{\dfrac{\mathrm{d}}{\dx}}
\newcommand{\sdydx}{\frac{\dy}{\dx}}
\newcommand{\sdudx}{\frac{\du}{\dx}}
\newcommand{\sdvdx}{\frac{\dv}{\dx}}
\newcommand{\sdydt}{\frac{\dy}{\dt}}
\newcommand{\sdtdx}{\frac{\dt}{\dx}}
\newcommand{\sdxdt}{\frac{\dx}{\dt}}
\newcommand{\sddx}{\frac{\mathrm{d}}{\dx}}

\newcommand{\fx}[1][x]{f\!\left(#1\right)}
\newcommand{\gx}[1][x]{g\!\left(#1\right)}
\newcommand{\hx}[1][x]{h\!\left(#1\right)}
\newcommand{\fdx}[1][x]{f^{\prime}\!\left(#1\right)}
\newcommand{\gdx}[1][x]{g^{\prime}\!\left(#1\right)}
\newcommand{\hdx}[1][x]{h^{\prime}\!\left(#1\right)}

\newcommand{\func}[2]{#1\!\left(#2\right)}
\newcommand{\funcd}[2]{#1^{\prime}\!\left(#2\right)}
\newcommand{\funcinv}[2]{#1^{-1}\!\left(#2\right)}

\newcommand{\colvec}[1]{\left(\begin{matrix}#1\end{matrix}\right)}

\newcommand{\fxinv}[1][x]{f^{-1}\!\left(#1\right)}
\newcommand{\gxinv}[1][x]{g^{-1}\!\left(#1\right)}
\newcommand{\hxinv}[1][x]{h^{-1}\!\left(#1\right)}
\renewcommand{\csc}{\operatorname{cosec}}

\newcommand{\ex}[1]{\mathrm{e}^{#1}}

%Assessments
\pointsinrightmargin %prints marks in the right hand margin
\bracketedpoints % Formats the points to have brackets around the marks.
\pointpoints{mark}{marks} %makes LaTeX use the "marks" instead of "points"
%\addpoints %adds various functionalities of the exam class.
\vpword{Marks} %changes the headings in the grading boxes
\vsword{Question total} %changes the headings in the grading boxes
\bvpword{Marks} %changes the headings in the grading boxes
\bvsword{Question total} %changes the headings in the grading boxes

\newcommand{\exmark}[1][A1]{\text{\textcolor{red}{[#1]}}}
\newcommand{\bmark}[1][A1]{\text{\textcolor{blue}{[#1]}}}
\totalformat{\textbf{Total \totalpoints\ marks}} %changes the formating of the \droptotalpoints command.

\newif\ifshortpaper

\newcommand{\examcover}[5][]{
\thispagestyle{empty}
\begin{center}
\includegraphics[scale=0.5]{Welly}\\
\vspace{0.25in}
\huge\textsc{{Wellington College}}\\
\huge\textsc{{Mathematics Department}}\\
\vspace{0.25in}
\LARGE
#2, #3\\
\normalsize
\vspace{0.25in}
\ifprintanswers
\huge{\textcolor{red}{MARKSCHEME}}\end{center}
\else
\ifshortpaper
\vspace{0.01in}
\else
\begin{tabular}{|C{1.5cm}|C{10cm}|}
\hline
Name:&\\[0.3cm]
\hline
Teacher: & \\[0.3cm]
\hline
\end{tabular}
\fi
\end{center}
You have #4 to complete the test.\\ There are \pointsinrange{Core} marks available.\\ 
\ifthenelse{\equal{#5}{yes}}{You may use a calculator.\\}{You may not use a calculator.\\}
\fi
#1
\vspace{0.25in}
\begin{flushright}
\gradetable
\end{flushright}
}

\newcommand{\examcovercb}[5][]{
\thispagestyle{empty}
\begin{center}
\includegraphics[scale=0.5]{Welly}\\
\vspace{0.25in}
\huge\textsc{{Wellington College}}\\
\huge\textsc{{Mathematics Department}}\\
\vspace{0.25in}
\LARGE
#2, #3\\
\normalsize
\vspace{0.25in}
\ifprintanswers
\huge{\textcolor{red}{MARKSCHEME}}\end{center}
\else
\ifshortpaper
\vspace{0.01in}
\else
\begin{tabular}{|C{1.5cm}|C{10cm}|}
\hline
Name:&\\[0.3cm]
\hline
Teacher: & \\[0.3cm]
\hline
\end{tabular}
\fi
\end{center}
You have #4 to complete the test.\\ There are \pointsinrange{Core} marks available for the core section \firstqinrange{Core}---\lastqinrange{Core}. Attempt these first before going on to the bonus section \firstqinrange{Bonus}---\lastqinrange{Bonus} which is worth \pointsinrange{Bonus} marks.\\ 
\ifthenelse{\equal{#5}{yes}}{You may use a calculator.\\}{You may not use a calculator.\\}
\fi
#1
\vspace{0.25in}
\begin{flushright}
\partialgradetable{Core}
\partialgradetable{Bonus}
\end{flushright}
}

\newcommand{\examdottedpage}{
\ifprintanswers

\else
\ifshortpaper

\else
\newpage
\fillwithdottedlines{\stretch{1}}
\newpage
\fi
\fi
}

\newcommand{\msexamnewpage}{
\ifshortpaper

\else
\newpage
\fi
}

\newcommand{\examnewpage}{
\ifshortpaper

\else
\ifprintanswers

\else
\newpage
\fi\fi
}

\newcommand{\solnordots}[2]{
\ifshortpaper

\else
\begin{solutionordottedlines}[\stretch{#1}]
#2 
\end{solutionordottedlines}
\fi
}

%Misc.
\newcommand{\st}{\textsuperscript{st}}
\newcommand{\nd}{\textsuperscript{nd}}
\newcommand{\rd}{\textsuperscript{rd}}
\newcommand{\nday}{\textsuperscript{th}}
\definecolor{forest}{rgb}{0,0.4,0} %a forest green colour
\newcolumntype{C}[1]{>{\centering\arraybackslash}p{#1}} %Allows a formatting of columns so they can all be made the same width

\newif\ifmuted
\newcommand{\mute}[1]{
\ifmuted

\else
#1
\fi 
}
\stackMath%Allows for the command which aligns maths in the solution box of a coursebook.

\newcommand{\button}[2][rectangle,draw,rounded corners]{
        \tikz[baseline=-0.6ex] \node [#1,rounded corners]{\myfont #2};}
\newcommand{\myfont}{\fontfamily{lmtt}\selectfont}
\newcommand{\moo}{Moo! \end{document}}%This is to fix the document when latex decides it has had enough of life

\newcommand{\greek}{
\begin{center}
\begin{tabular}{|C{1.5cm}|C{1.5cm}|C{1.5cm}|}
\hline
$\alpha$ & A & alpha \\
$\beta$ & B & beta\\
$\gamma$ & $\Gamma$ & gamma\\
$\delta$ & $\Delta$ & delta\\
$\varepsilon$ or $\epsilon$ & E & epsilon\\
$\zeta$ & Z & zeta\\
$\eta$ & H & eta\\
$\theta$ & $\Theta$ & theta\\
$\iota$ & I & iota\\
$\kappa$ & K & kappa\\
$\lambda$ & $\Lambda$ & lambda\\
$\mu$ & M & mu\\
$\nu$ & N & nu\\
$\xi$ & $\Xi$ & xi\\
$o$ & O & omicron\\
$\pi$ or $\varpi$ & $\Pi$ & pi\\
$\rho$ or $\varrho$ & P & rho\\
$\sigma$ or $\varsigma$ & $\Sigma$ & sigma\\
$\tau$ & T & tau\\
$\upsilon$ & $\Upsilon$ & upsilon\\
$\phi$ or $\varphi$ & $\Phi$&phi\\
$\chi$ & X & chi\\
$\psi$&$\Psi$&psi\\
$\omega$&$\Omega$&omega\\
\hline
\end{tabular}
\end{center}

\noindent Notice that Greek capital letters are not italicised in most mathematical contexts, so $X$ is a capital `ex', but X is a capital `chi'.

}

\tikzset{
BLUE/.style={circle,draw = blue, pattern color=blue, pattern = north west lines,inner sep=1mm},
RED/.style={circle,draw = red, pattern color=red, pattern = north east lines,inner sep=1mm},
PURPLE/.style={circle,draw = violet, pattern color=violet, pattern = crosshatch,inner sep=1mm}, 
stottleouter/.style={rounded corners=10, very thick, 
fill = gray, opacity = 0.35},
PARTICLE/.style={circle,draw = black, fill = gray,inner sep=1mm},
BOX/.style={draw, black, rectangle, inner sep = 2 mm},
DOT/.style={circle,draw = black, fill = black,inner sep=0.4mm},
PULLEY/.style={circle,draw=black,inner sep = 2mm},
stottleinner/.style={rounded corners=8, thin, fill = white},
attract/.style = {thick,
decoration={markings, mark = at position #1cm with {\arrow{stealth}}}, decoration={markings, mark = at position {-#1cm + 5pt} with {\arrow{stealth reversed}}}, postaction={decorate}},
attract/.default=0.3,
repel/.style = {thick,
decoration={markings, mark = at position {#1cm} with {\arrow{stealth reversed}}}, decoration={markings, mark = at position {-#1cm + 5pt} with {\arrow{stealth}}}, postaction={decorate}},
repel/.default=0.3,
roughsurface/.style = {draw = white, pattern color = gray, pattern = north west lines},
smoothsurface/.style = {draw = white, pattern color = gray, pattern = north east lines},
person/.pic = {\draw (0.2,0)--(0.5,0)--(1,2)--(1.5,0)--(1.8,0) ++ (-0.8,2) --(1,4) ++ (0,0.3) circle (0.3);
\draw (0.2,2.4) -- (1, 3.6)--(1.8,2.4); },
car/.pic = {
\draw [rounded corners = 3pt] (0.6,0.3)--(0,0.3)--(0,1.3)--(1,1.3)--(1.7,2.3)--(4,2.3) .. controls (4.6,1.3) .. (4.6,0.3) -- (4,0.3); 
\draw (0.9,0.3) circle (0.3);
\draw (3.7, 0.3) circle (0.3);
\draw [rounded corners = 2pt] (1.3,1.3) -- (1.8,2.1) -- (2.8,2.1) -- (2.8,1.3) -- cycle; 
\draw [rounded corners = 2pt] (3, 2.1) -- (3.7, 2.1) -- (4.1, 1.3) -- (3, 1.3)--cycle;
\draw (1.2, 0.3) -- (3.4, 0.3);},
trailer/.pic = {
\draw (0,0.5)--(0.3,0.5);
\draw (0.3,0.3) rectangle (2.5, 1.1);
\draw [fill=white](1.4,0.3) circle (0.3);},
stottletron/.pic={
\draw[stottleouter] (0,0) rectangle (6,3);
\draw[stottleinner] (0.3, 0.3) rectangle (5.7, 2.7);
\node[font=\scriptsize\scshape] (t1) at (4.5,0.15) {stottletron 2000};
},
pull/.style = {thick,
decoration={markings, mark = at position {#1} with {\arrow{stealth}}}, postaction={decorate}},
pull/.default=0.5,
crow/.pic = {\draw[fill = black] (1,0) -- (1.6,0) .. controls (1.6, -1) and (1.3, -1.5) .. (0.8,-2.4) ++ (0,0) .. controls (0.3,-1.5) and (0,-1) .. (0,0) -- (1.1,0);
\draw[fill = black] (0.8, 0.1) circle (0.3 and 0.5);},
iphone/.pic = {
\draw [rounded corners = 0.7cm,fill = gray!50] (0,0) rectangle (10,5);
\draw [rounded corners = 1mm,fill=white] (1.5, 0.3) rectangle (8.5, 4.7);
\draw [fill=gray!80](0.7, 2.5) circle (0.1);
\draw [rounded corners = 1mm,fill=gray!70] (1,2) rectangle (1.2,3);
\draw [fill=gray!60](9.25,2.5) circle (0.45);},
rope/.style={brown, thick,decorate,decoration={waves,radius=1mm,segment length = 0.5mm}}
}

\renewcommand\questionshook{%
  \def\makelabel##1{\hss\llap{\makebox[17pt][r]{##1}}}}
  
\newif\ifqpp

\newcommand{\Question}{\ifqpp
\newpage \question
\else
\question
\fi}
  
\renewcommand{\vec}[1]{\mathbf{#1}} 
\newcommand{\grav}{\mathrm{g}}

% Version History
% Ver 0.71 Added all the function commands AG
% Ver 0.72 Added \colvec, \sectioncover and inverse function commands AG
% Ver 0.73 Added color package and mark formatting for tests and language tweaks AJFS
% Ver 0.74 Added \notebook, \notes and \partnotes AJFS
% Ver 0.75 Added forest colour and \csc command AJFS
% Ver 0.76 Added package tabularx package and \newcolumntype for tables to have malleable column lengths AG
% Ver 0.77 Added the \printdiagrams conditional statement to speed up typesetting while editing AG
% Ver 0.78 Added multicol, tabstackengine, bigintsm, mathdots and hhline packages; \mybox and \myfont and also the patterns from the tikzlibrary AG
% Ver 0.79 Added \moo function AJFS
% Ver 0.791 Added \ex function AG
% Ver 0.8 Added \licence, \helpO, \helpU, \howTo, \makeMost, \greek functions AG
% Ver 0.8001 Added \mbox{} to \solnsc and \solncs to make more robust in the case where parts are used AJFS
% Ver 0.801 Added \solnsq, \solncq, \solnscq, \solncsq AJFS
% Ver 0.9 Reordered the class to be in sections which match the documentation and removed various marking commands to just leave \exmark and \bmark AG
% Ver 0.91 Changed \mybox to \button command and also changed the format of the command to rounded corners. \diagram{} now prints a box around "This is a placeholder for a diagram". Also \examcover, \examcovercb, \msexamnewpage, \examnewpage and \examdottedpage were added AG.
% Ver 0.915 Added \solnordots and added short paper functionality to \examdottedpage command AG
% Ver 0.916 Added \aref \apref and \abox to enable the right-margin printing of answer references in review exercises. AJFS 
% Ver 0.917 Added \diff command AG
% Ver 0.92 Added hierglf and pgfplots packages AG
% Ver 0.93 Added \twocol, \threecol command and adjusted column leader spacing. AJFS
% Ver 0.93 Added \sdiff command AJFS
% Ver 0.94 Added various tikz styles as per mechanics book AJFS
% Ver 0.94 Added shapes and decorations tikz libraries, iphone pic
% ver 0.95 Fixed three digit numbering misalignment
%			Added the \ifqpp and \Question commands
% ver 0.96 Redefine the \vec command to use bold notation
% ver 1.0.0 Add \testfrontcover{Name}{Date}
% ver 1.0.1 Add \grav command
% ver 1.1.0 Add \glossent and \glossitem
