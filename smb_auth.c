#include <stdio.h>
#include "valid.h"

int main(int argc, char ** argv)
{
  char * user, * pass, * server, * backup, * domain;
  
  if(argc < 6)
    return 1;
  
  user   = argv[1];
  pass   = argv[2];
  server = argv[3];
  backup = argv[4];
  domain = argv[5];
  
  if(Valid_User(user, pass, server, backup, domain) == NTV_NO_ERROR)
    return 0;
  
  return 1;
}
