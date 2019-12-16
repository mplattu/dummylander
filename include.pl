#!/usr/bin/perl

use strict;
# You can use JSON as well
use JSON::XS 'decode_json';
#use JSON 'decode_json';

# Tiny build function for HTML files allows you to:
# - Include files (<!-- include:path/to/file.html -->)
# - Remove unnecessary code (<!-- ifdef variable -->...<!-- endif -->)
# - Replace vars (<!-- var:variablename -->)

sub fatal_error {
  my ($message) = @_;
  print STDERR "$message\n";
  exit(1);
}

sub read_file_raw {
  my ($filename) = @_;

  open(F, "<$filename") || fatal_error("Could not open $filename for reading: $!");
  my @lines = <F>;
  close(F);

  my $joined = join('', @lines);
  return $joined;
}

sub read_file {
  my ($filename) = @_;
  my $raw = read_file_raw($filename);

  while ($raw =~ /(\<!\-\- include\:([^ ]+) \-\-\>)/) {
    my $inc_filename = $2;
    my $inc_tag = $1;
    my $new_content = read_file($inc_filename);
    $raw =~ s/$inc_tag/$new_content/;
  }

  while ($raw =~ /(\<!\-\- include-jsesc\:([^ ]+) \-\-\>)/) {
    my $inc_filename = $2;
    my $inc_tag = $1;
    my $new_content = read_file($inc_filename);
    $new_content =~ s/\n/ \\\n/g;
    $raw =~ s/$inc_tag/$new_content/;
  }

  return $raw;
}

sub process_ifs {
  my ($code, $vars) = @_;

  while ($code =~ /([ \t]*\<!\-\- ifdef:([^ ]+) \-\-\>\n?)/) {
    my $varname = $2;
    my $tag = $1;

    if ($vars->{$varname}) {
      # The variable is defined, remove tag
      $code =~ s/$tag//s;
    }
    else {
      # The variable is not defined, remove code
      $code =~ s/$tag.+?\<!\-\- endif \-\->\n?//s;
    }

  }

  # Remove all endif:s
  $code =~ s/[ \t]*\<!\-\- endif \-\->\n?//g;

  return $code;
}

sub process_vars {
  my ($code, $vars) = @_;

  my @missing_vars;

  while ($code =~ /([ \t]*\<!\-\- var:([^ ]+) \-\-\>\n?)/) {
    my $varname = $2;
    my $tag = $1;

    if (defined($vars->{$varname})) {
      # The variable is defined, replace tag with the value
      $code =~ s/$tag/$vars->{$varname}/s;
    }
    else {
      # Variable is missing - replace with dummy and push to error stach
      $code =~ s/$tag/MISSING/s;
      push(@missing_vars, '"'.$varname.'"');
    }
  }

  if (scalar(@missing_vars) > 0) {
    die "Following variables are missing: ".join(", ", @missing_vars);
  }
  return $code;
}

# Read file contents (process all includes)
my $file = read_file($ARGV[0]);

#print "FILE READ\n";

# Read JSON containing variables (e.g. '{"feature1":1,"feature2":1,"replace-me","this-value"}'')
my $vars = {};
if ($ARGV[1] ne "") {
  $vars = decode_json($ARGV[1]);
}

#print "JSON DECODED\n";

# Remove unnecessary code
$file = process_ifs($file, $vars);

#print "IFS PROCESSED\n";

# Replace with variables
$file = process_vars($file, $vars);

#print "VARS PROCESSED\n";

print "$file";
