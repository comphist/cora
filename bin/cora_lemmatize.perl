#!/usr/bin/perl

$no_pos = 0;

$num_args = $#ARGV + 1;
if($num_args < 2) {
    print "Usage:\nlemmatize.perl <lemma-dictionary> <input-file> [-n]\n\n";
    exit 1;
}

$dictfile = $ARGV[0];
$infile   = $ARGV[1];
if($num_args > 2 && $ARGV[2] eq "-n") {
    $no_pos = 1;
}

open(FILE,$dictfile) or die "Error: unable to open \"$dictfile\"\n";
if($no_pos) {
    while (<FILE>) {
        chomp;
        my($word,$lemma) = split(/\t/);
        $lemma_np{$word} = $lemma;
    }
}
else {
    while (<FILE>) {
        chomp;
        my($word,$tag,$lemma) = split(/\t/);
        $lemma_full{$word}{$tag} = $lemma;
        $lemma_np{$word} = $lemma;
    }
}
close FILE;

open(INFILE,$infile) or die "Error: unable to open \"$infile\"\n";
while (<INFILE>) {
    chomp;
    my $lemma;
    if($no_pos) {
        my $word = $_;
        $lemma = lemmatize_without_pos($word);
    }
    else {
        my($word,$tag) = split(/\t/);
        $lemma = lemmatize_with_pos($word, $tag);
    }

    print "$lemma\n";
}
close INFILE;

sub find_matching_entry {
    my $word = shift;
    my $pos  = shift;
    my ($base_pos, $morph) = split(/\./, $pos);

    if (exists $lemma_full{$word}{$pos}) {
        return $lemma_full{$word}{$pos};
    }
    while (($key, $value) = each $lemma_full{$word}) {
        if (index($key, $base_pos) == 0) {
            return $value;
        }
    }
    return 0;
}

sub lemmatize_with_pos {
    my $word = shift;
    my $pos  = shift;

    my $lc = lowercase($word);

    if ($word eq '') {
	return '';
    }
    if (exists $lemma_full{$word}) {
        $lemma = find_matching_entry($word, $pos);
        if($lemma) { return $lemma; }
    }
    if (exists $lemma_full{$lc}) {
        $lemma = find_matching_entry($lc, $pos);
        if($lemma) { return $lemma; }
    }
    if ($word =~ /^(.*)-(.+)$/ && exists $lemma_full{$2}) {
        $lemma = find_matching_entry($2, $pos);
        if($lemma) { return $lemma; }
    }

    for( $i=1; $i<length($word)-4; $i++ ) {
        my $x = substr($word,$i);
        my $X = ucfirst($x);
        if (exists $lemma_full{$x}) {
            $lemma = find_matching_entry($x, $pos);
            if($lemma) {
                return substr($word,0,$i).lc($lemma);
            }
        }
        elsif (exists $lemma{$X}) {
            $lemma = find_matching_entry($X, $pos);
            if($lemma) {
                return substr($word,0,$i).lc($lemma);
            }
        }
    }

    return lemmatize_without_pos($word);
}

sub lemmatize_without_pos {
    my $word = shift;
    my $lc = lowercase($word);

    if ($word eq '') {
	return '';
    }
    if (exists $lemma_np{$word}) {
        return $lemma_np{$word};
    }
    if (exists $lemma_np{$lc}) {
        return $lemma_np{$lc};
    }
    if ($word =~ /^(.*)-(.+)$/ && exists $lemma_np{$2}) {
        return $lemma_np{$2};
    }

    return "<unknown>";
}

sub lowercase {
    $s = shift;
    $s =~ tr/A-ZÄÖÜ/a-zäöü/;

    return $s;
}
