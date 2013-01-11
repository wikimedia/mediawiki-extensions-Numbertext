<?php
/**
 * Soros interpreter (see numbertext.org)
 *
 * @author Pavel Astakhov <pastakhov@yandex.ru>
 * @license LGPL/BSD dual license
 * @copyright (c) 2009-2010, László Németh
 */
class Soros {
/*
  private ArrayList<Pattern> patterns = new ArrayList<Pattern>();
  private ArrayList<String> values = new ArrayList<String>();
  private ArrayList<Boolean> begins = new ArrayList<Boolean>();
  private ArrayList<Boolean> ends = new ArrayList<Boolean>();
*/

    private $patterns = array();
    private $values = array();
    private $begins = array();
    private $ends = array();

/*
  private static String m = "\\\";#";
  private static String m2 = "$()|";
  private static String c = "\uE000\uE001\uE002\uE003";
  private static String c2 = "\uE004\uE005\uE006\uE007";
  private static String slash = "\uE000";
  private static String pipe = "\uE003";
*/
    private $m;
    private $m2;
    private $c;
    private $c2;
    private $slash;
    private $pipe;

  // pattern to recognize function calls in the replacement string
    private $func;

/*
  private boolean numbertext = false;
*/
    private $numbertext = false;

/*
  public Soros(String source) {
*/
    /**
     * @param string $source
     */
    public function __construct( $source ) {
        $this->m = array( "\\", "\"", ";", "#");
        $this->m2 = array( "$", "(", ")", "|");
        $this->c = array( json_decode('"\uE000"'), json_decode('"\uE001"'), json_decode('"\uE002"'), json_decode('"\uE003"'),);
        $this->c2 = array( json_decode('"\uE004"'), json_decode('"\uE005"'), json_decode('"\uE006"'), json_decode('"\uE007"'),);
        $this->slash = array( json_decode('"\uE000"') );
        $this->pipe= array( json_decode('"\uE003"') );
/*
    source = translate(source, m, c, "\\") 	// \\, \", \;, \# -> \uE000..\uE003
	.replaceAll("(#[^\n]*)?(\n|$)", ";");	// remove comments
*/
        $source = self::translate($source, $this->m, $this->c, "\\");
        $source = preg_replace("/(#[^\n]*)?(\n|$)/", ";", $source);
/*
    if (source.indexOf("__numbertext__") > -1) {
	numbertext = true;
	source = source.replace("__numbertext__", "0+(0|[1-9]\\d*) $1\n");
    }
*/
        if ( strpos($source, "__numbertext__") !== false ) {
            $this->numbertext = true;
            $source = str_replace( "__numbertext__", "0+(0|[1-9]\\d*) $1\n", $source);
        }
/*
    Pattern p = Pattern.compile("^\\s*(\"[^\"]*\"|[^\\s]*)\\s*(.*[^\\s])?\\s*$");
    for (String s : source.split(";")) {
*/
        foreach ( split(";", $source) as $s ) {
/*
	Matcher sp = p.matcher(s);
	if (!s.equals("") && sp.matches()) {
 */
            if ( $s != "" && preg_match("/^\\s*(\"[^\"]*\"|[^\\s]*)\\s*(.*[^\\s])?\\s*$/", $s, $sp) > 0 ) {
/*
	    s = translate(sp.group(1).replaceFirst("^\"", "").replaceFirst("\"$",""),
		c.substring(1), m.substring(1), "");
	    s = s.replace(slash, "\\\\"); // -> \\, ", ;, #
	    String s2 = "";
 */
                //$c = array_shift($this->c);
                //$m = array_shift($this->m);
                $s = self::translate(preg_replace("/\"$/", "", preg_replace("/^\"/", "", $sp[1], 1), 1),
                $this->c, $this->m, "");
                $s = str_replace($this->slash[0], "\\\\", $s);
                $s2 = "";
/*
	    if (sp.group(2) != null) s2 = sp.group(2).replaceFirst("^\"", "").replaceFirst("\"$","");
 */
                if ( isset($sp[2]) ) $s2 = preg_replace("/\"$/", "", preg_replace("/^\"/", "", $sp[2], 1), 1);
/*
	    s2 = translate(s2, m2, c2, "\\"); 	// \$, \(, \), \| -> \uE004..\uE007
	    s2 = s2.replaceAll("(\\$\\d|\\))\\|\\$", "$1||\\$"); // $()|$() -> $()||$()
	    s2 = translate(s2, c, m, ""); 	// \uE000..\uE003-> \, ", ;, #
	    s2 = translate(s2, m2, c, ""); 	// $, (, ), | -> \uE000..\uE003
	    s2 = translate(s2, c2, m2, ""); 	// \uE004..\uE007 -> $, (, ), |
 */

                $s2 = self::translate($s2, $this->m2, $this->c2, "\\");
                $s2 = preg_replace("/(\\$\\d|\\))\\|\\$/", "$1||\\$", $s2);
                $s2 = self::translate($s2, $this->c, $this->m, "");
                $s2 = self::translate($s2, $this->m2, $this->c, "");
                $s2 = self::translate($s2, $this->c2, $this->m2, "");

/*
	    s2 = s2.replaceAll("[$]", "\\$")	// $ -> \$
		.replaceAll("\uE000(\\d)", "\uE000\uE001\\$$1\uE002") // $n -> $(\n)
		.replaceAll("\\\\(\\d)", "\\$$1") // \[n] -> $[n]
		.replace("\\n", "\n");		  // \n -> [new line]
 */

                $s2 = preg_replace("/[$]/", "\\$", $s2); // $ -> \$
                $s2 = preg_replace("/".$this->c[0]."(\\d)/", $this->c[0].$this->c[1]."\\$$1".$this->c[2], $s2); // $n -> $(\n)
                $s2 = preg_replace("/\\\\(\\d)/", "\\$$1", $s2); // \[n] -> $[n]
                $s2 = preg_replace("/\\n/", "\n", $s2); // \n -> [new line]
/*
	    patterns.add(Pattern.compile("^" + s.replaceFirst("^\\^", "")
		.replaceFirst("\\$$", "") + "$"));
	    begins.add(s.startsWith("^"));
	    ends.add(s.endsWith("$"));
	    values.add(s2);
 */
                $this->patterns[] = "^" . preg_replace("/\\$$/", "", preg_replace("/^\\^/", "", $s, 1), 1) . "$";
                $this->begins[] = (mb_substr($s, 0, 1) == "^");
                $this->ends[] = (mb_substr($s, -1) == "$");
                $this->values[] = $s2;
            }
        }
//      private static Pattern func = Pattern.compile(translate(
        $this->func = self::translate(
//              "(?:\\|?(?:\\$\\()+)?" +		// optional nested calls
                "(?:\\|?(?:\\$\\()+)?" .
//              "(\\|?\\$\\(([^\\(\\)]*)\\)\\|?)" +	// inner call (2 subgroups)
                "(\\|?\\$\\(([^\\(\\)]*)\\)\\|?)" .
//              "(?:\\)+\\|?)?",			// optional nested calls
                "(?:\\)+\\|?)?",
//              m2, c, "\\"));				// \$, \(, \), \| -> \uE000..\uE003
                $this->m2, $this->c, "\\");
    }

/*
  public String run(String inputss) {
 */
    /**
     *
     * @param string $input
     * @return string
     */
    public function run( $input ) {
/*
    if (!numbertext) return run(input, true, true);
    return run(input, true, true).trim().replaceAll("  +", " ");
 */
        if ( !$this->numbertext ) return $this->run3( $input, true, true );
        return preg_replace("/  +/", " ", trim($this->run3($input, true, true)) );
    }

/*
  private String run(String input, boolean begin, boolean end) {
 */
    /**
     *
     * @param string $input
     * @param string $begin
     * @param string $end
     * @return string
     */
    private function run3( $input, $begin, $end) {
/*
    for (int i = 0; i < patterns.size(); i++) {
	if ((!begin && begins.get(i)) || (!end && ends.get(i))) continue;
	Matcher m = patterns.get(i).matcher(input);
	if (!m.matches()) continue;
 */
        $count = count($this->patterns);
        for ($i=0; $i<$count; $i++) {
            if( (!$begin && $this->begins[$i]) || (!$end && $this->ends[$i])) continue;
            if( !preg_match("/".$this->patterns[$i]."/", $input, $m) ) continue;
/*
	String s = m.replaceAll(values.get(i));
	Matcher n = func.matcher(s);
	while (n.find()) {
 */
            $s = preg_replace("/".$this->patterns[$i]."/", $this->values[$i],  $m[0]);
            preg_match_all("/".$this->func."/u", $s, $n, PREG_OFFSET_CAPTURE);
            while ( count($n[0]) > 0 ) {
                //              n.start()            n.group()            n.start(1)           n.group(1)           n.start(2)           n.group(2)
                //MWDebug::log( $n[0][0][1] . "=>" . $n[0][0][0] . ", " . $n[1][0][1] . "=>" . $n[1][0][0] . ", " . $n[2][0][1] . "=>" . $n[2][0][0] );
/*
	    boolean b = false;
	    boolean e = false;
 */
                $b = false;
                $e = false;
/*
	    if (n.group(1).startsWith(pipe) || n.group().startsWith(pipe)) b = true;
	    else if (n.start() == 0) b = begin;
 */
                if ( mb_substr($n[1][0][0], 0, 1) == $this->pipe[0] || mb_substr($n[0][0][0], 0, 1) == $this->pipe[0] ) { $b = true; }
                elseif ($n[0][0][1] == 0) { $b = $begin; }
/*
	    if (n.group(1).endsWith(pipe) || n.group().endsWith(pipe)) e = true;
	    else if (n.end() == s.length()) e = end;
 */
                if ( mb_substr($n[1][0][0], -1) == $this->pipe[0] || mb_substr($n[0][0][0], -1) == $this->pipe[0] ) { $e = true; }
                elseif ( $n[0][0][1] + strlen($n[0][0][0]) == strlen($s) ) $e = $end;
/*
	    s = s.substring(0, n.start(1)) + run(n.group(2), b, e) + s.substring(n.end(1));
	    n = func.matcher(s);
 */
                $s = substr($s, 0, $n[1][0][1]) . $this->run3($n[2][0][0], $b, $e) . substr($s, $n[1][0][1] + strlen($n[1][0][0]));
                preg_match_all("/".$this->func."/u", $s, $n, PREG_OFFSET_CAPTURE);
            }
/*
        return s;
 */
            return $s;
        }
/*
    return "";
 */
        return "";
    }

/*
  private static String translate(String s, String chars, String chars2, String delim) {
 */
    /**
     *
     * @param string $s
     * @param string $chars
     * @param string $chars2
     * @param string $delim
     * @return string
     */
    private static function translate($s, $chars, $chars2, $delim) {
/*
    for (int i = 0; i < chars.length(); i++) {
	s = s.replace(delim + chars.charAt(i), "" + chars2.charAt(i));
    }
    return s;
 */
        $count = count($chars);
        for ($i=0; $i < $count; $i++) {
            $s = str_replace($delim . $chars[$i], $chars2[$i], $s);
        }
        return $s;
    }
}
