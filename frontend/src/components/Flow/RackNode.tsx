import clsx from 'clsx';
import { Button } from 'flowbite-react';
import React, { memo } from 'react';


export default memo(({ data, id }: any) => {
  return (
    <>
      <div className="group">
        <div className={clsx("border bg-white dark:bg-gray-800 h-40 w-20 flex items-center justify-center text-center text-ellipsis overflow-hidden", data.selected && 'border-red-500')}>
          <p>{data.label}</p>
        </div>
        {!data.displayOnly &&
          <div className='group-hover:flex flex-wrap gap-2 pt-2 hidden'>
            <Button className='nodrag inline' onClick={() => data.edit({ id: id, data: data })}>Edit</Button>
            <Button className='inline nodrag' color="failure" onClick={() => data.delete({ id: id, data: data })}>Delete</Button>
          </div>
        }
      </div>
    </>
  );
});
